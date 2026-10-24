<?php
declare(strict_types=1);
namespace App\Enum;
enum ListingType:string {
 case SALE='sale'; case RENT='rent';
 public function label(): string { return $this===self::SALE ? 'Venda' : 'Aluguel'; }
}
