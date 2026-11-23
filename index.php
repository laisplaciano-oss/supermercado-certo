<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
check_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = ?');
        $stmt->execute([$id]);
        flash('Produto excluído com sucesso.');
    }
    redirect();
}

$search = trim($_GET['q'] ?? '');
$category = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
$sql = 'SELECT p.*, c.nome AS categoria_nome FROM produtos p INNER JOIN categorias c ON c.id = p.categoria_id';
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(p.nome LIKE ? OR p.descricao LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($category) {
    $where[] = 'p.categoria_id = ?';
    $params[] = $category;
}
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.nome ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();
$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();
$totalProdutos = (int)$pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$totalCategorias = (int)$pdo->query('SELECT COUNT(*) FROM categorias')->fetchColumn();
$pageTitle = 'Produtos';
require __DIR__ . '/includes/header.php';
$notice = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<section class="hero">
    <div><p class="eyebrow">PAINEL DE CONTROLE</p><h1>Seus produtos,<br><em>em perfeita ordem.</em></h1>
    <p class="hero-text">Cadastre, consulte, edite e organize os produtos e suas categorias em um só lugar.</p></div>
    <div class="hero-flower">✿</div>
</section>
<?php if ($notice): ?><div class="alert <?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endif; ?>
<section class="stats">
    <article class="stat-card"><span>Produtos cadastrados</span><strong><?= $totalProdutos ?></strong></article>
    <article class="stat-card"><span>Categorias criadas</span><strong><?= $totalCategorias ?></strong></article>
</section>
<div class="section-heading"><div><p class="eyebrow">ESTOQUE</p><h2>Lista de produtos</h2></div><a class="button" href="produto_form.php">＋ Novo produto</a></div>
<form class="filters" method="get">
    <input type="search" name="q" placeholder="Buscar produto..." value="<?= e($search) ?>">
    <select name="categoria"><option value="">Todas as categorias</option>
        <?php foreach ($categorias as $cat): ?><option value="<?= $cat['id'] ?>" <?= $category === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['nome']) ?></option><?php endforeach; ?>
    </select>
    <button class="button button-soft" type="submit">Filtrar</button>
    <a class="clear-link" href="index.php">Limpar</a>
</form>
<div class="table-wrap">
<table>
    <thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Quantidade</th><th>Ações</th></tr></thead>
    <tbody>
    <?php if (!$produtos): ?><tr><td colspan="5" class="empty">Nenhum produto encontrado. Que tal cadastrar o primeiro?</td></tr><?php endif; ?>
    <?php foreach ($produtos as $p): ?>
    <tr>
        <td><strong><?= e($p['nome']) ?></strong><?php if ($p['descricao']): ?><small><?= e($p['descricao']) ?></small><?php endif; ?></td>
        <td><span class="tag"><?= e($p['categoria_nome']) ?></span></td>
        <td class="price">R$ <?= number_format((float)$p['preco'], 2, ',', '.') ?></td>
        <td><?= (int)$p['quantidade'] ?></td>
        <td class="actions"><a class="action-link" href="produto_form.php?id=<?= (int)$p['id'] ?>">Editar</a>
            <form method="post" onsubmit="return confirm('Deseja realmente excluir este produto?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="delete-link" type="submit">Excluir</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<div class="bottom-note">♡ Dica: mantenha as categorias atualizadas para encontrar seus produtos com facilidade.</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
