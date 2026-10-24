<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Agency;
use App\Entity\Property;
use App\Entity\PropertyImage;
use App\Enum\ListingType;
use App\Enum\PropertyModerationStatus;
use App\Enum\PropertyStatus;
use App\Enum\PropertyType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:seed-demo-catalog',
    description: 'Cria imobiliárias e imóveis fictícios para demonstração local do catálogo.'
)]
final class SeedDemoCatalogCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!in_array($this->kernel->getEnvironment(), ['dev', 'test'], true)) {
            $io->error('Este seed contém dados fictícios e só pode ser executado nos ambientes dev e test.');

            return Command::FAILURE;
        }

        $agencyRepository = $this->entityManager->getRepository(Agency::class);
        $propertyRepository = $this->entityManager->getRepository(Property::class);
        $createdAgencies = 0;
        $createdProperties = 0;
        $createdImages = 0;

        $agencies = [
            [
                'name' => 'Costa Azul Imóveis', 'slug' => 'demo-costa-azul-imoveis',
                'email' => 'contato@demo-costaazul.invalid', 'phone' => '(12) 3000-1001',
                'whatsapp' => '551230001001', 'city' => 'Caraguatatuba',
                'description' => 'Imobiliária demonstrativa especializada em imóveis residenciais no litoral norte paulista.',
            ],
            [
                'name' => 'Serra & Mar Negócios Imobiliários', 'slug' => 'demo-serra-mar-imoveis',
                'email' => 'contato@demo-serraemar.invalid', 'phone' => '(12) 3000-1002',
                'whatsapp' => '551230001002', 'city' => 'Ubatuba',
                'description' => 'Perfil fictício para demonstrar casas de temporada, apartamentos e imóveis próximos à praia.',
            ],
            [
                'name' => 'Vale Lar Imobiliária', 'slug' => 'demo-vale-lar-imobiliaria',
                'email' => 'contato@demo-valelar.invalid', 'phone' => '(12) 3000-1003',
                'whatsapp' => '551230001003', 'city' => 'São Sebastião',
                'description' => 'Imobiliária fictícia com opções residenciais e terrenos para diferentes necessidades.',
            ],
        ];

        $agencyEntities = [];
        foreach ($agencies as $data) {
            $agency = $agencyRepository->findOneBy(['slug' => $data['slug']]);
            if (!$agency instanceof Agency) {
                $agency = (new Agency())
                    ->setName($data['name'])
                    ->setSlug($data['slug'])
                    ->setEmail($data['email'])
                    ->setPhone($data['phone'])
                    ->setWhatsapp($data['whatsapp'])
                    ->setCity($data['city'])
                    ->setState('SP')
                    ->setDescription($data['description']);
                $agency->approve();
                $this->entityManager->persist($agency);
                ++$createdAgencies;
            }
            $agencyEntities[$data['slug']] = $agency;
        }

        $this->entityManager->flush();

        $properties = [
            ['agency' => 'demo-costa-azul-imoveis', 'slug' => 'demo-casa-familiar-indaiá', 'title' => 'Casa familiar no Indaiá', 'description' => 'Casa ampla com ambientes iluminados, quintal e espaço para aproveitar os dias em família. Anúncio fictício para demonstração.', 'type' => PropertyType::HOUSE, 'listing' => ListingType::SALE, 'price' => 780000, 'area' => 185, 'bedrooms' => 3, 'bathrooms' => 2, 'parking' => 2, 'city' => 'Caraguatatuba', 'neighborhood' => 'Indaiá', 'image' => 'house.svg', 'alt' => 'Ilustração demonstrativa de uma casa com jardim'],
            ['agency' => 'demo-costa-azul-imoveis', 'slug' => 'demo-apartamento-centro-caragua', 'title' => 'Apartamento moderno no Centro', 'description' => 'Apartamento prático, com varanda e áreas sociais integradas, próximo a comércio e serviços. Anúncio fictício para demonstração.', 'type' => PropertyType::APARTMENT, 'listing' => ListingType::SALE, 'price' => 495000, 'area' => 78, 'bedrooms' => 2, 'bathrooms' => 2, 'parking' => 1, 'city' => 'Caraguatatuba', 'neighborhood' => 'Centro', 'image' => 'apartment.svg', 'alt' => 'Ilustração demonstrativa de um edifício residencial'],
            ['agency' => 'demo-costa-azul-imoveis', 'slug' => 'demo-sobrado-martim-de-sa', 'title' => 'Sobrado perto da Martim de Sá', 'description' => 'Sobrado com varanda, espaço externo e ambientes para receber amigos após um dia de praia. Anúncio fictício para demonstração.', 'type' => PropertyType::HOUSE, 'listing' => ListingType::RENT, 'price' => 4200, 'area' => 142, 'bedrooms' => 3, 'bathrooms' => 3, 'parking' => 2, 'city' => 'Caraguatatuba', 'neighborhood' => 'Martim de Sá', 'image' => 'house.svg', 'alt' => 'Ilustração demonstrativa de uma casa com jardim'],
            ['agency' => 'demo-serra-mar-imoveis', 'slug' => 'demo-casa-praia-itagua', 'title' => 'Casa acolhedora no Itaguá', 'description' => 'Casa com espaços integrados e quintal, em bairro conhecido pela gastronomia e pelo comércio local. Anúncio fictício para demonstração.', 'type' => PropertyType::HOUSE, 'listing' => ListingType::SALE, 'price' => 920000, 'area' => 210, 'bedrooms' => 4, 'bathrooms' => 3, 'parking' => 2, 'city' => 'Ubatuba', 'neighborhood' => 'Itaguá', 'image' => 'house.svg', 'alt' => 'Ilustração demonstrativa de uma casa com jardim'],
            ['agency' => 'demo-serra-mar-imoveis', 'slug' => 'demo-apartamento-pereque-acu', 'title' => 'Apartamento próximo ao Perequê-Açu', 'description' => 'Apartamento funcional com varanda e boa distribuição dos ambientes, ideal para moradia ou temporada. Anúncio fictício para demonstração.', 'type' => PropertyType::APARTMENT, 'listing' => ListingType::RENT, 'price' => 2800, 'area' => 64, 'bedrooms' => 2, 'bathrooms' => 1, 'parking' => 1, 'city' => 'Ubatuba', 'neighborhood' => 'Perequê-Açu', 'image' => 'apartment.svg', 'alt' => 'Ilustração demonstrativa de um edifício residencial'],
            ['agency' => 'demo-serra-mar-imoveis', 'slug' => 'demo-terreno-praia-dura', 'title' => 'Terreno em região residencial', 'description' => 'Terreno para projeto residencial, com informações ilustrativas para demonstrar filtros e detalhes do catálogo. Anúncio fictício.', 'type' => PropertyType::LAND, 'listing' => ListingType::SALE, 'price' => 350000, 'area' => 420, 'bedrooms' => null, 'bathrooms' => null, 'parking' => null, 'city' => 'Ubatuba', 'neighborhood' => 'Praia Dura', 'image' => 'land.svg', 'alt' => 'Ilustração demonstrativa de um terreno com vegetação'],
            ['agency' => 'demo-vale-lar-imobiliaria', 'slug' => 'demo-casa-maresias', 'title' => 'Casa com área externa em Maresias', 'description' => 'Casa de estilo praiano com área externa e espaço para descanso. Valores e características são fictícios. Anúncio demonstrativo.', 'type' => PropertyType::HOUSE, 'listing' => ListingType::SALE, 'price' => 1150000, 'area' => 195, 'bedrooms' => 3, 'bathrooms' => 3, 'parking' => 2, 'city' => 'São Sebastião', 'neighborhood' => 'Maresias', 'image' => 'house.svg', 'alt' => 'Ilustração demonstrativa de uma casa com jardim'],
            ['agency' => 'demo-vale-lar-imobiliaria', 'slug' => 'demo-apartamento-barequecaba', 'title' => 'Apartamento em Barequeçaba', 'description' => 'Apartamento compacto para ilustrar anúncios de locação no litoral. Valores e características são fictícios.', 'type' => PropertyType::APARTMENT, 'listing' => ListingType::RENT, 'price' => 3100, 'area' => 70, 'bedrooms' => 2, 'bathrooms' => 2, 'parking' => 1, 'city' => 'São Sebastião', 'neighborhood' => 'Barequeçaba', 'image' => 'apartment.svg', 'alt' => 'Ilustração demonstrativa de um edifício residencial'],
            ['agency' => 'demo-vale-lar-imobiliaria', 'slug' => 'demo-terreno-pontal-da-cruz', 'title' => 'Terreno residencial no Pontal da Cruz', 'description' => 'Lote ilustrativo para construção residencial, usado para demonstrar as opções de venda do portal. Anúncio fictício.', 'type' => PropertyType::LAND, 'listing' => ListingType::SALE, 'price' => 290000, 'area' => 360, 'bedrooms' => null, 'bathrooms' => null, 'parking' => null, 'city' => 'São Sebastião', 'neighborhood' => 'Pontal da Cruz', 'image' => 'land.svg', 'alt' => 'Ilustração demonstrativa de um terreno com vegetação'],
        ];

        foreach ($properties as $data) {
            $property = $propertyRepository->findOneBy(['slug' => $data['slug']]);
            if (!$property instanceof Property) {
                $property = (new Property())
                    ->setAgency($agencyEntities[$data['agency']])
                    ->setSlug($data['slug'])
                    ->setTitle($data['title'])
                    ->setDescription($data['description'])
                    ->setPropertyType($data['type'])
                    ->setListingType($data['listing'])
                    ->setPrice($data['price'])
                    ->setAreaM2($data['area'])
                    ->setBedrooms($data['bedrooms'])
                    ->setBathrooms($data['bathrooms'])
                    ->setParkingSpaces($data['parking'])
                    ->setCity($data['city'])
                    ->setState('SP')
                    ->setNeighborhood($data['neighborhood'])
                    ->setStatus(PropertyStatus::PUBLISHED)
                    ->setModerationStatus(PropertyModerationStatus::APPROVED);
                $this->entityManager->persist($property);
                ++$createdProperties;
            }

            if ($property->getImages()->isEmpty()) {
                $image = (new PropertyImage())
                    ->setFilePath('images/demo-properties/' . $data['image'])
                    ->setThumbnailPath('images/demo-properties/' . $data['image'])
                    ->setAltText($data['alt'])
                    ->setSortOrder(0);
                $property->addImage($image);
                ++$createdImages;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Seed concluído. Novas imobiliárias: %d; novos imóveis: %d; imagens associadas: %d. Nenhum usuário foi criado ou alterado.',
            $createdAgencies,
            $createdProperties,
            $createdImages,
        ));
        $io->note('Todos os nomes, contatos, preços e anúncios criados por este comando são fictícios. Execute novamente com segurança: registros existentes não são sobrescritos.');

        return Command::SUCCESS;
    }
}
