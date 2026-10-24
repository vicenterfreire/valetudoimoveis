<?php

declare(strict_types=1);

namespace App\Controller\Agency;

use App\Entity\User;
use App\Repository\ContactEventRepository;
use App\Repository\PropertyRepository;
use App\Security\AgencyAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/painel/metricas', name: 'agency_analytics_')]
#[IsGranted('ROLE_AGENCY_STAFF')]
final class AnalyticsController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        AgencyAccess $access,
        ContactEventRepository $events,
        PropertyRepository $properties,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $agency = $access->requireActiveAgency($user);
        [$from, $until, $fromInput, $toInput] = $this->dateRange($request);
        $summary = $events->summarizeForAgency($agency, $from, $until);
        $propertyRows = $events->propertySummaryForAgency($agency, $from, $until);

        $statusCounts = [];
        foreach ($properties->findForAgency($agency) as $property) {
            $status = $property->getStatus()->value;
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }

        if ($request->query->getBoolean('export')) {
            return $this->exportCsv($fromInput, $toInput, $summary, $statusCounts, $propertyRows);
        }

        return $this->render('agency/analytics/index.html.twig', [
            'agency' => $agency,
            'fromDate' => $fromInput,
            'toDate' => $toInput,
            'summary' => $summary,
            'statusCounts' => $statusCounts,
            'totalProperties' => array_sum($statusCounts),
            'propertyRows' => $propertyRows,
        ]);
    }

    /** @return array{0:\DateTimeImmutable,1:\DateTimeImmutable,2:string,3:string} */
    private function dateRange(Request $request): array
    {
        $today = new \DateTimeImmutable('today');
        $fromInput = $this->validDate($request->query->get('from'), $today->modify('-29 days')->format('Y-m-d'));
        $toInput = $this->validDate($request->query->get('to'), $today->format('Y-m-d'));
        $from = \DateTimeImmutable::createFromFormat('!Y-m-d', $fromInput);
        $to = \DateTimeImmutable::createFromFormat('!Y-m-d', $toInput);

        if (!$from || !$to || $from > $to) {
            $this->addFlash('error', 'O período informado é inválido. Selecione uma data inicial anterior ou igual à final.');
            $fromInput = $today->modify('-29 days')->format('Y-m-d');
            $toInput = $today->format('Y-m-d');
            $from = new \DateTimeImmutable($fromInput);
            $to = new \DateTimeImmutable($toInput);
        }

        if ($from->diff($to)->days > 365) {
            $this->addFlash('error', 'O período máximo para consulta é de 366 dias.');
            $fromInput = $to->modify('-365 days')->format('Y-m-d');
            $from = new \DateTimeImmutable($fromInput);
        }

        return [$from->setTime(0, 0), $to->modify('+1 day')->setTime(0, 0), $fromInput, $toInput];
    }

    private function validDate(mixed $value, string $fallback): string
    {
        if (!is_string($value)) {
            return $fallback;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
    }

    private function safeCsvCell(string $value): string
    {
        return preg_match('/^[=+@\\-\\t\\r]/u', $value) === 1 ? "'" . $value : $value;
    }

    private function exportCsv(string $from, string $to, array $summary, array $statusCounts, array $propertyRows): Response
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Não foi possível preparar a exportação.');
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['Casa & Costa — Relatório de métricas']);
        fputcsv($stream, ['Período', $from, $to]);
        fputcsv($stream, []);
        fputcsv($stream, ['Métrica', 'Quantidade']);
        fputcsv($stream, ['Visualizações de páginas de imóveis', $summary['views']]);
        fputcsv($stream, ['Cliques de contato registrados', $summary['contacts']]);
        fputcsv($stream, ['Contatos via WhatsApp', $summary['whatsapp']]);
        fputcsv($stream, ['Contatos via telefone', $summary['phone']]);
        fputcsv($stream, ['Contatos via e-mail', $summary['email']]);
        fputcsv($stream, []);
        fputcsv($stream, ['Anúncios por status atual', 'Quantidade']);
        foreach ($statusCounts as $status => $count) {
            fputcsv($stream, [$status, $count]);
        }
        fputcsv($stream, []);
        fputcsv($stream, ['Imóvel', 'Visualizações', 'Cliques de contato']);
        foreach ($propertyRows as $row) {
            fputcsv($stream, [$this->safeCsvCell((string) $row['title']), (int) $row['views'], (int) $row['contacts']]);
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return new Response($content === false ? '' : $content, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="metricas-%s-a-%s.csv"', $from, $to),
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
