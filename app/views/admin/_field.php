<?php
/**
 * Campo do formulário do CMS (gerado a partir da definição). Recursivo para group/list.
 * @var \Teia\Core\View $view
 * @var array $field     definição do campo
 * @var string $key
 * @var mixed $value
 * @var string $path     caminho com pontos (secao.campo.item.subcampo) — usado em erros e uploads
 * @var string $name     nome do input (c[secao][campo]...)
 * @var array $errors
 * @var int $depth       profundidade de listas (placeholder __K{depth}__ nos modelos)
 * @var \Teia\Cms\Render $h
 */
defined('TEIA_ROOT') || exit;

$type = $field['type'];
$id = 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $path);
$required = !empty($field['required']);
$error = $errors[$path] ?? null;
$help = $field['help'] ?? '';
$describedBy = trim(($help !== '' ? $id . '-ajuda ' : '') . ($error ? $id . '-erro' : ''));
$label = $field['label'] . ($required ? ' *' : '');
?>
<?php if ($type === 'group'): ?>
    <fieldset class="field-group">
        <legend><?= e($field['label']) ?></legend>
        <?php if ($help !== ''): ?><p class="field__help"><?= e($help) ?></p><?php endif; ?>
        <?php foreach ($field['fields'] as $subKey => $sub): ?>
            <?= $view->partial('admin/_field', [
                'field' => $sub, 'key' => $subKey, 'value' => $value[$subKey] ?? null,
                'path' => $path . '.' . $subKey, 'name' => $name . '[' . $subKey . ']',
                'errors' => $errors, 'depth' => $depth, 'h' => $h,
            ]) ?>
        <?php endforeach; ?>
    </fieldset>

<?php elseif ($type === 'list'):
    $items = is_array($value) ? $value : [];
    $itemLabelKey = $field['item_label'] ?? null;
    $placeholder = '__K' . $depth . '__';
    $max = (int) ($field['max_items'] ?? 50);
    $min = (int) ($field['min_items'] ?? ($required ? 1 : 0));
    $renderItem = function (array $item, string $itemKey, int $index) use ($view, $field, $path, $name, $errors, $depth, $h, $itemLabelKey): string {
        $title = $itemLabelKey && isset($item[$itemLabelKey]) && is_string($item[$itemLabelKey]) && $item[$itemLabelKey] !== '' ? $item[$itemLabelKey] : 'Novo item';
        $html = '<div class="list-item" data-item><div class="list-item__head">'
            . '<span class="list-item__title" data-item-title>' . e(mb_strimwidth($title, 0, 70, '…')) . '</span>'
            . '<span class="list-item__actions">'
            . '<button type="button" class="btn btn--small btn--ghost" data-move="up" aria-label="Mover para cima">↑</button>'
            . '<button type="button" class="btn btn--small btn--ghost" data-move="down" aria-label="Mover para baixo">↓</button>'
            . '<button type="button" class="btn btn--small btn--danger" data-remove>Remover</button>'
            . '</span></div><div class="list-item__body">';
        foreach ($field['fields'] as $subKey => $sub) {
            $html .= $view->partial('admin/_field', [
                'field' => $sub, 'key' => $subKey, 'value' => $item[$subKey] ?? null,
                'path' => $path . '.' . $itemKey . '.' . $subKey,
                'name' => $name . '[' . $itemKey . '][' . $subKey . ']',
                'errors' => $errors, 'depth' => $depth + 1, 'h' => $h,
                'itemLabel' => $subKey === $itemLabelKey,
            ]);
        }
        return $html . '</div></div>';
    };
    // Modelo de item novo: valores padrão dos subcampos.
    $blank = [];
    foreach ($field['fields'] as $subKey => $sub) {
        $blank[$subKey] = $sub['type'] === 'list' ? [] : ($sub['type'] === 'group' ? [] : ($sub['type'] === 'boolean' ? false : (string) ($sub['default'] ?? '')));
    }
?>
    <fieldset class="field-list<?= $error ? ' has-error' : '' ?>" data-list data-max="<?= $max ?>" data-min="<?= $min ?>" data-placeholder="<?= e($placeholder) ?>"<?= $describedBy ? ' aria-describedby="' . e($describedBy) . '"' : '' ?>>
        <legend><?= e($label) ?></legend>
        <?php if ($help !== ''): ?><p class="field__help" id="<?= e($id) ?>-ajuda"><?= e($help) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="field__error" id="<?= e($id) ?>-erro"><?= e($error) ?></p><?php endif; ?>
        <div class="list-items" data-items>
            <?php $i = 0; foreach ($items as $itemKey => $item): ?>
                <?= $renderItem(is_array($item) ? $item : [], is_string($itemKey) && preg_match('/^[a-z0-9_]{1,24}$/', $itemKey) ? $itemKey : 'i' . $i, $i) ?>
            <?php $i++; endforeach; ?>
        </div>
        <template data-template><?= $renderItem($blank, $placeholder, 0) ?></template>
        <button type="button" class="btn btn--small btn--secondary" data-add>+ Adicionar item</button>
        <p class="field__help js-only-hint">Para adicionar, remover ou reordenar itens, é preciso ter o JavaScript ativado.</p>
    </fieldset>

<?php elseif ($type === 'image' || $type === 'file'):
    $current = is_string($value) ? $value : '';
?>
    <div class="field<?= $error ? ' has-error' : '' ?>">
        <span class="field__label" id="<?= e($id) ?>-rotulo"><?= e($label) ?></span>
        <?php if ($help !== ''): ?><p class="field__help" id="<?= e($id) ?>-ajuda"><?= e($help) ?></p><?php endif; ?>
        <div class="upload">
            <?php if ($current !== ''): ?>
                <div class="upload__preview">
                    <?php if ($type === 'image'): ?>
                        <?= $h->picture($current, 'Imagem atual', ['loading' => 'lazy']) ?>
                    <?php else: $fileUrl = $h->fileUrl($current); ?>
                        <?php if ($fileUrl): ?><a href="<?= e($fileUrl) ?>" download>Baixar arquivo atual (PDF)</a><?php else: ?><span class="muted">Arquivo atual indisponível</span><?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="muted"><?= $type === 'image' ? 'Nenhuma imagem.' : 'Nenhum arquivo enviado.' ?></p>
            <?php endif; ?>
            <input type="hidden" name="<?= e($name) ?>" value="<?= e($current) ?>">
            <label class="upload__input">
                <span><?= $current !== '' ? 'Substituir por:' : 'Enviar:' ?></span>
                <input type="file" id="<?= e($id) ?>" name="u[<?= e($path) ?>]" accept="<?= $type === 'image' ? 'image/jpeg,image/png,image/webp' : 'application/pdf' ?>" aria-labelledby="<?= e($id) ?>-rotulo"<?= $describedBy ? ' aria-describedby="' . e($describedBy) . '"' : '' ?>>
            </label>
            <?php if (!$required && $current !== ''): ?>
                <label class="check"><input type="checkbox" name="r[<?= e($path) ?>]" value="1"> Remover <?= $type === 'image' ? 'imagem' : 'arquivo' ?></label>
            <?php endif; ?>
        </div>
        <?php if ($error): ?><p class="field__error" id="<?= e($id) ?>-erro"><?= e($error) ?></p><?php endif; ?>
    </div>

<?php elseif ($type === 'boolean'): ?>
    <div class="field field--check">
        <input type="hidden" name="<?= e($name) ?>" value="0">
        <label class="check"><input type="checkbox" id="<?= e($id) ?>" name="<?= e($name) ?>" value="1"<?= $value ? ' checked' : '' ?>> <?= e($field['label']) ?></label>
        <?php if ($help !== ''): ?><p class="field__help"><?= e($help) ?></p><?php endif; ?>
    </div>

<?php else:
    $max = (int) ($field['max'] ?? ($type === 'textarea' ? 2000 : 200));
    $text = is_scalar($value) ? (string) $value : '';
?>
    <div class="field<?= $error ? ' has-error' : '' ?>">
        <label class="field__label" for="<?= e($id) ?>"><?= e($label) ?></label>
        <?php if ($help !== ''): ?><p class="field__help" id="<?= e($id) ?>-ajuda"><?= e($help) ?></p><?php endif; ?>
        <?php if ($type === 'textarea'): ?>
            <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= (int) ($field['rows'] ?? 4) ?>" maxlength="<?= $max ?>"<?= $required ? ' required' : '' ?><?= $describedBy ? ' aria-describedby="' . e($describedBy) . '"' : '' ?><?= !empty($itemLabel) ? ' data-item-label' : '' ?>><?= e($text) ?></textarea>
            <?php if (!empty($field['rich'])): ?><p class="field__hint">Formatação: **negrito**, *itálico*, [texto do link](https://endereco). HTML não é aceito.</p><?php endif; ?>
        <?php else:
            $inputType = match ($type) { 'email' => 'email', 'phone' => 'tel', default => 'text' };
        ?>
            <input type="<?= $inputType ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($text) ?>" maxlength="<?= $max ?>"<?= $type === 'link' ? ' inputmode="url" placeholder="https://… ou #secao" spellcheck="false"' : '' ?><?= $required ? ' required' : '' ?><?= $describedBy ? ' aria-describedby="' . e($describedBy) . '"' : '' ?><?= !empty($itemLabel) ? ' data-item-label' : '' ?>>
            <?php if ($type === 'link' && $text === '' && !$required): ?><p class="field__hint field__hint--todo">Link pendente: o item aparece no site sem levar a lugar nenhum.</p><?php endif; ?>
        <?php endif; ?>
        <?php if ($error): ?><p class="field__error" id="<?= e($id) ?>-erro"><?= e($error) ?></p><?php endif; ?>
    </div>
<?php endif; ?>
