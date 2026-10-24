<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\AdminUsageReportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/relatorios', name: 'admin_usage_reports_')]
#[IsGranted('ROLE_ADMIN')]
final class UsageReportController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, AdminUsageReportRepository $reports): Response
    {
        [$from, $until, $fromInput, $toInput] = $this->dateRange($request);
        $agencyId = $this->agencyFilter($request);
        $overview = $reports->overview($agencyId);
        $listingTrend = $this->completeListingMonths($from, $until, $reports->listingTrend($from, $until, $agencyId));
        $contactTrend = $this->completeContactMonths($from, $until, $reports->contactTrend($from, $until, $agencyId));
        $agencies = $reports->agenciesForFilter($agencyId);

        if ($request->query->getBoolean('export')) {
            return $this->exportCsv($fromInput, $toInput, $agencyId, $overview, $listingTrend, $contactTrend, $agencies);
        }

        return $this->render('admin/reports/index.html.twig', [
            'fromDate' => $fromInput,
            'toDate' => $toInput,
            'selectedAgency' => $agencyId,
            'agencies' => $agencies,
            'overview' => $overview,
            'listingTrend' => $listingTrend,
            'contactTrend' => $contactTrend,
            'retentionDays' => 365,
        ]);
    }

    /** @return array{0:\DateTimeImmutable,1:\DateTimeImmutable,2:string,3:string} */
    private function dateRange(Request $request): array
    {
        $today = new \DateTimeImmutable('today');
        $defaultFrom = $today->modify('-29 days')->format('Y-m-d');
        $fromInput = $this->validDate($request->query->get('from'), $defaultFrom);
        $toInput = $this->validDate($request->query->get('to'), $today->format('Y-m-d'));
        $from = \DateTimeImmutable::createFromFormat('!Y-m-d', $fromInput);
        $to = \DateTimeImmutable::createFromFormat('!Y-m-d', $toInput);

        if (!$from || !$to || $from > $to) {
            $this->addFlash('error', 'Período inválido. A data inicial deve ser anterior ou igual à final.');
            $fromInput = $defaultFrom;
            $toInput = $today->format('Y-m-d');
            $from = new \DateTimeImmutable($fromInput);
            $to = new \DateTimeImmutable($toInput);
        }

        if ($from->diff($to)->days > 365) {
            $this->addFlash('error', 'O período máximo de consulta é de 366 dias.');
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

    private function agencyFilter(Request $request): ?int
    {
        $raw = $request->query->get('agency');
        if ($raw === null || $raw === '') {
            return null;
        }
        $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            $this->addFlash('error', 'O filtro de imobiliária é inválido.');
            return null;
        }
        return $id;
    }

    /** @param list<array{month:string,total:int}> $rows @return list<array{month:string,total:int}> */
    private function completeListingMonths(\DateTimeImmutable $from, \DateTimeImmutable $until, array $rows): array
    {
        $byMonth = [];
        foreach ($rows as $row) {
            $byMonth[$row['month']] = $row['total'];
        }
        $result = [];
        $month = $from->modify('first day of this month')->setTime(0, 0);
        $lastMonth = $until->modify('-1 day')->modify('first day of this month')->setTime(0, 0);
        while ($month <= $lastMonth) {
            $key = $month->format('Y-m');
            $result[] = ['month' => $key, 'total' => $byMonth[$key] ?? 0];
            $month = $month->modify('+1 month');
        }
        return $result;
    }

    /** @param list<array{month:string,views:int,contacts:int}> $rows @return list<array{month:string,views:int,contacts:int}> */
    private function completeContactMonths(\DateTimeImmutable $from, \DateTimeImmutable $until, array $rows): array
    {
        $byMonth = [];
        foreach ($rows as $row) {
            $byMonth[$row['month']] = ['views' => $row['views'], 'contacts' => $row['contacts']];
        }
        $result = [];
        $month = $from->modify('first day of this month')->setTime(0, 0);
        $lastMonth = $until->modify('-1 day')->modify('first day of this month')->setTime(0, 0);
        while ($month <= $lastMonth) {
            $key = $month->format('Y-m');
            $values = $byMonth[$key] ?? ['views' => 0, 'contacts' => 0];
            $result[] = ['month' => $key, 'views' => $values['views'], 'contacts' => $values['contacts']];
            $month = $month->modify('+1 month');
        }
        return $result;
    }

    private function safeCsvCell(string $value): string
    {
        return preg_match('/^[=+@\-\t\r]/u', $value) === 1 ? "'" . $value : $value;
    }

    /** @param array<string,int> $overview @param list<array{month:string,total:int}> $listingTrend @param list<array{month:string,views:int,contacts:int}> $contactTrend @param list<array{id:int,name:string,status:string}> $agencies */
    private function exportCsv(string $from, string $to, ?int $agencyId, array $overview, array $listingTrend, array $contactTrend, array $agencies): Response
    {
        $agencyName = 'Todas as imobiliárias';
        foreach ($agencies as $agency) {
            if ($agency['id'] === $agencyId) {
                $agencyName = $agency['name'];
                break;
            }
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Não foi possível preparar a exportação do relatório.');
        }
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['ValeTudo Imóveis — Relatório de utilização']);
        fputcsv($stream, ['Período', $from, $to]);
        fputcsv($stream, ['Filtro de imobiliária', $this->safeCsvCell($agencyName)]);
        fputcsv($stream, []);
        fputcsv($stream, ['Indicador', 'Quantidade']);
        foreach ($overview as $key => $value) {
            fputcsv($stream, [$key, $value]);
        }
        fputcsv($stream, []);
        fputcsv($stream, ['Evolução de anúncios', 'Mês', 'Quantidade']);
        foreach ($listingTrend as $row) {
            fputcsv($stream, ['Anúncios cadastrados', $row['month'], $row['total']]);
        }
        fputcsv($stream, []);
        fputcsv($stream, ['Evolução de contatos', 'Mês', 'Visualizações', 'Cliques de contato']);
        foreach ($contactTrend as $row) {
            fputcsv($stream, ['Eventos', $row['month'], $row['views'], $row['contacts']]);
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return new Response($content === false ? '' : $content, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="relatorio-utilizacao-%s-a-%s.csv"', $from, $to),
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
