<?php
/**
 * КЛЁВАЯ ДАЧА — данные сайта для публичных страниц.
 *
 * Подключается из index.php, mobile.php и avito/render_reviews.php:
 *   kd_public_reviews()      — опубликованные отзывы с сайта (смешиваются с отзывами Авито)
 *   kd_gallery_html($v)      — вкладки и панели фотогалереи ($v: 'desktop' | 'mobile')
 *   kd_videos_html($v)       — видео главной страницы
 * Сами функции живут в content.php. Любой сбой чтения данных не ломает страницу:
 * блоки просто остаются пустыми (причина пишется в booking/data/booking.log).
 */

declare(strict_types=1);

if (!defined('KD_BOOKING')) {
    define('KD_BOOKING', true);
}
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/content.php';
