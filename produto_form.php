<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
check_csrf();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$produto = ['nome'=>'', 'descricao'=>'', 'preco'=>'', 'quantidade'=>'0', 'categoria_id'=>''];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { flash('Produto não encontrado.', 'error'); redirect(); }
    $produto = $found;
}
$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $preco = str_replace(',', '.', trim($_POST['preco'] ?? ''));
    $quantidade = filter_var($_POST['quantidade'] ?? null, FILTER_VALIDATE_INT);
    $categoriaId = filter_var($_POST['categoria_id'] ?? null, FILTER_VALIDATE_INT);
    if ($nome === '') $errors[] = 'Informe o nome do produto.';
    if (!is_numeric($preco) || (float)$preco < 0) $errors[] = 'Informe um preço válido.';
    if ($quantidade === false || $quantidade < 0) $errors[] = 'Informe uma quantidade válida.';
    if (!$categoriaId) $errors[] = 'Selecione uma categoria.';
    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE produtos SET nome=?, descricao=?, preco=?, quantidade=?, categoria_id=? WHERE id=?');
            $stmt->execute([$nome, $descricao, $preco, $quantidade, $categoriaId, $id]);
            flash('Produto atualizado com sucesso.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO produtos (nome, descricao, preco, quantidade, categoria_id) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nome, $descricao, $preco, $quantidade, $categoriaId]);
            flash('Produto cadastrado com sucesso.');
        }
        redirect();
    }
    $produto = compact('nome','descricao','preco','quantidade') + ['categoria_id'=>$categoriaId];
}
$pageTitle = $id ? 'Editar produto' : 'Novo produto';
require __DIR__ . '/includes/header.php';
?>
<div class="form-shell">
    <a class="back-link" href="index.php">← Voltar para produtos</a>
    <p class="eyebrow">CADASTRO</p><h1><?= $id ? 'Editar produto' : 'Adicionar produto' ?></h1>
    <p class="muted">Preencha os dados abaixo para manter seu catálogo organizado.</p>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <?php if (!$categorias): ?><div class="alert error">Antes de cadastrar produtos, crie pelo menos uma categoria.</div><a class="button" href="categoria_form.php">Criar categoria</a>
    <?php else: ?>
    <form method="post" class="form-card">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Nome do produto<input name="nome" required maxlength="120" value="<?= e((string)$produto['nome']) ?>" placeholder="Ex.: Arroz integral"></label>
        <label>Descrição <span class="optional">(opcional)</span><textarea name="descricao" rows="3" maxlength="500" placeholder="Detalhes do produto..."><?= e((string)$produto['descricao']) ?></textarea></label>
        <div class="form-grid">
            <label>Preço (R$)<input name="preco" type="number" min="0" step="0.01" required value="<?= e((string)$produto['preco']) ?>" placeholder="0,00"></label>
            <label>Quantidade<input name="quantidade" type="number" min="0" step="1" required value="<?= e((string)$produto['quantidade']) ?>"></label>
        </div>
        <label>Categoria<select name="categoria_id" required><option value="">Selecione...</option><?php foreach ($categorias as $cat): ?><option value="<?= $cat['id'] ?>" <?= (int)$produto['categoria_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['nome']) ?></option><?php endforeach; ?></select></label>
        <div class="form-actions"><a class="clear-link" href="index.php">Cancelar</a><button class="button" type="submit">Salvar produto</button></div>
    </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
