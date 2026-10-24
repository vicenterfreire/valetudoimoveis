<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PrivateRouteAccessTest extends WebTestCase
{
    public function testAdminAreaRedirectsAnonymousVisitorToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/imobiliarias');

        self::assertResponseRedirects('/entrar');
    }

    public function testAgencyTeamAreaRedirectsAnonymousVisitorToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/painel/equipe');

        self::assertResponseRedirects('/entrar');
    }

    public function testAgencyPropertiesAreaRedirectsAnonymousVisitorToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/painel/imoveis');

        self::assertResponseRedirects('/entrar');
    }
}
