<?php
declare(strict_types=1);
namespace App\Enum;
enum PropertyStatus:string { case DRAFT='draft'; case PUBLISHED='published'; case PAUSED='paused'; case SOLD='sold'; case RENTED='rented'; case ARCHIVED='archived'; }
