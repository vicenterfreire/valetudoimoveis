<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'], format: 'xml')]
    public function index(PropertyRepository $properties, UrlGeneratorInterface $urls): Response
    {
        $entries = [[
            'loc' => $urls->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'lastmod' => (new \DateTimeImmutable())->format('Y-m-d'),
        ]];

        foreach ($properties->findAllPublicForSitemap() as $property) {
            $entries[] = [
                'loc' => $urls->generate('property_show', ['id' => $property->getId(), 'slug' => $property->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        return $this->render('public/sitemap.xml.twig', ['entries' => $entries], new Response('', Response::HTTP_OK, ['Content-Type' => 'application/xml; charset=UTF-8']));
    }
}
