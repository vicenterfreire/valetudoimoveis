<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\ListingType;
use App\Enum\PropertyType;
use App\Repository\PropertyRepository;
use App\Repository\PropertyHighlightRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private const PAGE_SIZE = 12;
    private const MAX_FILTER_TEXT_LENGTH = 100;
    private const MAX_PRICE = 9999999999.99;
    private const MAX_ROOM_COUNT = 20;
    private const MAX_AREA_M2 = 1000000.00;

    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(Request $request, PropertyRepository $repository, PropertyHighlightRepository $highlights): Response
    {
        $filters = [];
        $errors = [];
        $queryParams = $request->query->all();

        foreach (['city', 'neighborhood'] as $key) {
            $raw = $queryParams[$key] ?? '';
            if (!is_string($raw) && !is_numeric($raw)) {
                $errors[$key] = 'Informe um texto válido.';
                $filters[$key] = '';
                continue;
            }

            $value = trim((string) $raw);
            if (mb_strlen($value) > self::MAX_FILTER_TEXT_LENGTH) {
                $errors[$key] = 'O texto deve ter no máximo 100 caracteres.';
                $value = mb_substr($value, 0, self::MAX_FILTER_TEXT_LENGTH);
            }
            $filters[$key] = $value;
        }

        $listingType = $queryParams['listingType'] ?? '';
        $filters['listingType'] = is_string($listingType) && ($listingType === '' || ListingType::tryFrom($listingType) !== null)
            ? $listingType
            : '';
        if ($filters['listingType'] === '' && $listingType !== '') {
            $errors['listingType'] = 'A finalidade selecionada é inválida.';
        }

        $propertyType = $queryParams['propertyType'] ?? '';
        $filters['propertyType'] = is_string($propertyType) && ($propertyType === '' || PropertyType::tryFrom($propertyType) !== null)
            ? $propertyType
            : '';
        if ($filters['propertyType'] === '' && $propertyType !== '') {
            $errors['propertyType'] = 'O tipo de imóvel selecionado é inválido.';
        }

        foreach (['minPrice', 'maxPrice'] as $key) {
            $raw = $queryParams[$key] ?? '';
            $value = is_scalar($raw) ? trim((string) $raw) : '';
            $price = $this->validPrice($value);
            if (array_key_exists($key, $queryParams) && !is_scalar($raw)) {
                $errors[$key] = 'Informe um único valor de preço.';
                $value = '';
            } elseif ($value !== '' && $price === null) {
                $errors[$key] = 'Informe um preço válido, positivo ou zero, com até duas casas decimais.';
                $value = '';
            } elseif ($price !== null) {
                $value = number_format($price, 2, '.', '');
            }
            $filters[$key] = $value;
        }

        $minimum = $this->validPrice($filters['minPrice']);
        $maximum = $this->validPrice($filters['maxPrice']);
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            $errors['priceRange'] = 'O preço mínimo não pode ser maior que o preço máximo. Ajuste os valores para pesquisar.';
            $filters['minPrice'] = '';
            $filters['maxPrice'] = '';
        }

        foreach (['minArea', 'maxArea'] as $key) {
            $raw = $queryParams[$key] ?? '';
            $value = is_scalar($raw) ? trim((string) $raw) : '';
            $area = $this->validArea($value);
            if (array_key_exists($key, $queryParams) && !is_scalar($raw)) {
                $errors[$key] = 'Informe um único valor de área.';
                $value = '';
            } elseif ($value !== '' && $area === null) {
                $errors[$key] = 'Informe uma área válida entre 0 e 1.000.000 m², com até duas casas decimais.';
                $value = '';
            } elseif ($area !== null) {
                $value = number_format($area, 2, '.', '');
            }
            $filters[$key] = $value;
        }

        $minimumArea = $this->validArea($filters['minArea']);
        $maximumArea = $this->validArea($filters['maxArea']);
        if ($minimumArea !== null && $maximumArea !== null && $minimumArea > $maximumArea) {
            $errors['areaRange'] = 'A área mínima não pode ser maior que a área máxima.';
            $filters['minArea'] = '';
            $filters['maxArea'] = '';
        }

        foreach (['bedrooms', 'bathrooms', 'parkingSpaces'] as $key) {
            $raw = $queryParams[$key] ?? '';
            $value = is_scalar($raw) ? trim((string) $raw) : '';
            if (array_key_exists($key, $queryParams) && !is_scalar($raw)) {
                $errors[$key] = 'Informe uma única quantidade.';
                $filters[$key] = '';
                continue;
            }
            if ($value === '') {
                $filters[$key] = '';
                continue;
            }

            if (!ctype_digit($value) || (int) $value > self::MAX_ROOM_COUNT) {
                $errors[$key] = 'Selecione uma quantidade entre 0 e 20.';
                $filters[$key] = '';
                continue;
            }
            $filters[$key] = (string) (int) $value;
        }

        $sortValue = $queryParams['sort'] ?? 'recent';
        $sort = is_string($sortValue) && in_array($sortValue, ['recent', 'price_asc', 'price_desc'], true)
            ? $sortValue
            : 'recent';

        $total = $repository->countPublic($filters);
        $pageCount = max(1, (int) ceil($total / self::PAGE_SIZE));
        $pageValue = $queryParams['page'] ?? '1';
        $page = is_scalar($pageValue) && ctype_digit((string) $pageValue) ? max(1, (int) $pageValue) : 1;
        $page = min($page, $pageCount);

        return $this->render('public/home.html.twig', [
            'properties' => $repository->searchPublic($filters, self::PAGE_SIZE, ($page - 1) * self::PAGE_SIZE, $sort),
            'featuredHighlights' => $highlights->findPublicActive(6),
            'filters' => $filters,
            'filterErrors' => $errors,
            'sort' => $sort,
            'page' => $page,
            'pageCount' => $pageCount,
            'total' => $total,
        ]);
    }

    private function validArea(string $value): ?float
    {
        if ($value === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            return null;
        }

        $area = (float) $value;
        return is_finite($area) && $area >= 0 && $area <= self::MAX_AREA_M2 ? $area : null;
    }

    private function validPrice(string $value): ?float
    {
        if ($value === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            return null;
        }

        $price = (float) $value;
        return is_finite($price) && $price >= 0 && $price <= self::MAX_PRICE ? $price : null;
    }
}
