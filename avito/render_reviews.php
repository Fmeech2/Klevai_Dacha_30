<?php
require_once __DIR__ . '/lib.php';

// Отзывы, оставленные на сайте и опубликованные хозяином (booking/content.php)
$kdPublic = dirname(__DIR__) . '/booking/public.php';
if (is_file($kdPublic)) {
    require_once $kdPublic;
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Пропускаем только https-ссылки (на всякий случай)
// и файлы вложений, загруженные на этот же сайт (uploads/reviews/...)
function safe_url(?string $u): string
{
    if ($u === null) {
        return '';
    }
    if (str_starts_with($u, 'https://')) {
        return $u;
    }
    return preg_match('~^uploads/reviews/[A-Za-z0-9_.-]+$~', $u) ? $u : '';
}

// Возвращает карточки отзывов (без <section> и заголовка: они уже есть на странице).
// Отзывы с Авито и с сайта идут одной лентой по дате написания: новые сверху.
// Если отзывов нет, возвращает пустую строку.
function render_reviews(): string
{
    try {
        $data = get_reviews_data();
    } catch (Throwable $e) {
        $data = null;
    }

    $reviews = array_values(array_filter($data['reviews'] ?? [], fn($r) => $r['text'] !== ''));
    foreach ($reviews as &$r) {
        $r['source'] = 'avito';
    }
    unset($r);

    if (function_exists('kd_public_reviews')) {
        $reviews = array_merge($reviews, kd_public_reviews());
    }
    if (!$reviews) {
        return '';
    }
    usort($reviews, fn($a, $b) => $b['createdAt'] <=> $a['createdAt']);

    $tz        = new DateTimeZone('Europe/Astrakhan');
    $hasPhotos = false;

    ob_start();
    foreach ($reviews as $r):
        $dt   = (new DateTimeImmutable('@' . $r['createdAt']))->setTimezone($tz);
        $date = $dt->format('d.m.Y') . ' в ' . $dt->format('H:i');
        $photos = array_filter(
            $r['images'] ?? [],
            fn($p) => safe_url($p['thumb'] ?? null) && safe_url($p['full'] ?? null)
        );
        $hasPhotos = $hasPhotos || $photos;
?>
      <div class="review_card">
        <div class="review_avatar">
          <img src="img/Avatar.png" alt="Аватар" class="review_avatar_img" loading="lazy">
        </div>

        <div class="review_content">
          <div class="review_header">
            <span class="review_name"><?= h($r['name']) ?></span>
            <span class="review_meta">
              <span class="review_date"><?= h($date) ?></span>
<?php if (($r['source'] ?? '') === 'avito'): ?>
              <span class="review_source">Отзыв с Авито</span>
<?php endif; ?>
            </span>
          </div>
          <p class="review_text">
            <?= nl2br(h($r['text'])) ?>
          </p>
<?php if ($photos): ?>
          <div class="review_photos">
<?php foreach ($photos as $p): ?>
            <a class="review_photo" href="<?= h($p['full']) ?>" target="_blank" rel="noopener noreferrer">
              <img src="<?= h($p['thumb']) ?>" alt="Фото из отзыва" loading="lazy" referrerpolicy="no-referrer">
            </a>
<?php endforeach; ?>
          </div>
<?php endif; ?>
<?php $videos = array_filter($r['videos'] ?? [], fn($v) => safe_url($v['file'] ?? null)); ?>
<?php if ($videos): ?>
          <div class="review_videos">
<?php foreach ($videos as $v): ?>
            <video class="review_video" controls preload="metadata" playsinline src="<?= h($v['file']) ?>"></video>
<?php endforeach; ?>
          </div>
<?php endif; ?>
        </div>
      </div>
<?php
    endforeach;

    // Окно просмотра фото выводим один раз и только если фото есть
    if ($hasPhotos):
?>
      <dialog class="photo_lightbox" id="photo_lightbox">
        <button type="button" class="photo_lightbox_close" aria-label="Закрыть"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg></button>
        <button type="button" class="photo_lightbox_nav photo_lightbox_prev" aria-label="Предыдущее фото"><svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M15.5 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        <img class="photo_lightbox_img" src="" alt="Фото из отзыва" referrerpolicy="no-referrer">
        <button type="button" class="photo_lightbox_nav photo_lightbox_next" aria-label="Следующее фото"><svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M8.5 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
      </dialog>
      <script>
        (function () {
          var dlg = document.getElementById('photo_lightbox');
          if (!dlg || !dlg.showModal) return; // старый браузер: останутся обычные ссылки на фото
          var img = dlg.querySelector('.photo_lightbox_img');
          var prev = dlg.querySelector('.photo_lightbox_prev');
          var next = dlg.querySelector('.photo_lightbox_next');
          var group = [], idx = 0;

          function show(i) {
            idx = (i + group.length) % group.length;
            img.src = group[idx].href;
          }

          document.addEventListener('click', function (e) {
            var a = e.target.closest('.review_photo');
            if (!a) return;
            e.preventDefault();
            group = Array.prototype.slice.call(a.parentNode.querySelectorAll('.review_photo'));
            prev.hidden = next.hidden = group.length < 2;
            show(group.indexOf(a));
            dlg.showModal();
          });

          prev.addEventListener('click', function () { show(idx - 1); });
          next.addEventListener('click', function () { show(idx + 1); });
          dlg.querySelector('.photo_lightbox_close').addEventListener('click', function () { dlg.close(); });
          dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); }); // клик по фону
          dlg.addEventListener('close', function () { img.src = ''; });
          document.addEventListener('keydown', function (e) {
            if (!dlg.open || group.length < 2) return;
            if (e.key === 'ArrowLeft') show(idx - 1);
            if (e.key === 'ArrowRight') show(idx + 1);
          });
        })();
      </script>
<?php
    endif;

    return ob_get_clean();
}