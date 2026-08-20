<?php
/**
 * @var \App\Models\Page $page
 * @var array<int,array{label:string,url:?string}> $breadcrumbs
 * @var string $blocksHtml
 */
?>
<?= view('public.partials.breadcrumbs', ['items' => $breadcrumbs ?? []]) ?>
<div class="page-header">
  <h1><?= e($page->getAttribute('h1') ?: $page->getAttribute('title')) ?></h1>
</div>
<?= $blocksHtml ?>
