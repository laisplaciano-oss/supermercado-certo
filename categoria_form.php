<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
check_csrf();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT) ?: null;
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') $errors[] = 'Informe o nome da categoria.';
        if (!$errors) {
            try {
                if ($id) {
                    $stmt = $pdo->prepare('UPDATE categorias SET nome=? WHERE id=?');
                    $stmt->execute([$nome, $id]);
                    flash('Categoria atualizada com sucesso.');
                } else {
                    $stmt = $pdo->prepare('INSERT INTO categorias (nome) VALUES (?)');
                    $stmt->execute([$nome]);
                    flash('Categoria cadastrada com sucesso.');
                }
                redirect('categoria_form.php');
            } catch (PDOException $e) {
                $errors[] = 'Não foi possível salvar. Verifique se já existe uma categoria com esse nome.';
            }
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                $stmt = $pdo->prepare('DELETE FROM categorias WHERE id=?');
                $stmt->execute([$id]);
                flash('Categoria excluída com sucesso.');
            } catch (PDOException $e) {
                flash('Esta categoria possui produtos vinculados. Altere ou exclua esses produtos antes.', 'error');
            }
        }
        redirect('categoria_form.php');
    }
}
$editId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM categorias WHERE id=?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch();
}
$categorias = $pdo->query('SELECT c.*, COUNT(p.id) AS total_produtos FROM categorias c LEFT JOIN produtos p ON p.categoria_id=c.id GROUP BY c.id ORDER BY c.nome')->fetchAll();
$pageTitle = 'Categorias';
require __DIR__ . '/includes/header.php';
$notice = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<div class="section-heading"><div><p class="eyebrow">ORGANIZAÇÃO</p><h1>Categorias</h1><p class="muted">Agrupe seus produtos para facilitar a consulta.</p></div><a class="button button-soft" href="index.php">Ver produtos</a></div>
<?php if ($notice): ?><div class="alert <?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
<div class="category-layout">
    <section class="form-card">
        <h2><?= $edit ? 'Editar categoria' : 'Nova categoria' ?></h2>
        <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <label>Nome da categoria<input name="nome" required maxlength="80" value="<?= e($edit['nome'] ?? ($_POST['nome'] ?? '')) ?>" placeholder="Ex.: Mercearia"></label>
            <button class="button" type="submit"><?= $edit ? 'Atualizar categoria' : 'Cadastrar categoria' ?></button>
            <?php if ($edit): ?> <a class="clear-link" href="categoria_form.php">Cancelar edição</a><?php endif; ?>
        </form>
    </section>
    <section class="table-wrap">
        <table><thead><tr><th>Categoria</th><th>Produtos</th><th>Ações</th></tr></thead><tbody>
        <?php if (!$categorias): ?><tr><td colspan="3" class="empty">Nenhuma categoria cadastrada.</td></tr><?php endif; ?>
        <?php foreach ($categorias as $cat): ?><tr><td><strong><?= e($cat['nome']) ?></strong></td><td><?= (int)$cat['total_produtos'] ?></td><td class="actions"><a class="action-link" href="categoria_form.php?editar=<?= (int)$cat['id'] ?>">Editar</a><form method="post" onsubmit="return confirm('Excluir esta categoria?');"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>"><button class="delete-link" type="submit">Excluir</button></form></td></tr><?php endforeach; ?>
        </tbody></table>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
