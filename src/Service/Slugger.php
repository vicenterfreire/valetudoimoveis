<?php
declare(strict_types=1);
namespace App\Service;
class Slugger { public function slugify(string $s):string{$s=mb_strtolower(trim($s));$a=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);$s=$a!==false?$a:$s;$s=preg_replace('/[^a-z0-9]+/','-',$s)??'';return trim($s,'-')?:'item';} }
