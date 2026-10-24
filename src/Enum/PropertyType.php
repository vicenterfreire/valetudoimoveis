<?php
declare(strict_types=1);
namespace App\Enum;
enum PropertyType:string {
 case HOUSE='house'; case APARTMENT='apartment'; case LAND='land'; case COMMERCIAL='commercial'; case OTHER='other';
 public function label(): string { return match($this) { self::HOUSE=>'Casa', self::APARTMENT=>'Apartamento', self::LAND=>'Terreno', self::COMMERCIAL=>'Comercial', self::OTHER=>'Outro' }; }
}
