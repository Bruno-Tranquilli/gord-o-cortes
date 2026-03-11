<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Painel Admin | Gordão Cortes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <header class="topbar">
        <div class="brand">
            <span class="logo-mark">GC</span>
            <div>
                <h1>Painel Administrativo</h1>
                <p>Gordão Cortes</p>
            </div>
        </div>

        <nav>
            <a href="index.php">Cardápio Cliente</a>
        </nav>

        <div class="header-actions">
            <button id="logout-btn" class="btn btn-outline">Sair</button>
        </div>
    </header>

    <main class="section">
        <section id="admin-guard" class="panel hidden"></section>

        <section id="admin-content" class="hidden">
            <div class="section-head">
                <h3>Gestão Geral</h3>
                <p>Gerencie cortes, preços, usuários e agendamentos.</p>
            </div>

            <div class="admin-grid">
                <div class="panel">
                    <h4>Novo / Editar Corte</h4>
                    <form id="cut-form" class="tight-form">
                        <input type="hidden" id="cut-id" />
                        <label>Nome<input id="cut-name" required /></label>
                        <label>Descrição<textarea id="cut-description" rows="2"></textarea></label>
                        <label>Duração (min)<input id="cut-duration" type="number" value="30" min="5" required /></label>
                        <label>Preço (R$)<input id="cut-price" type="number" step="0.01" min="1" required /></label>
                        <label>Imagem (URL)<input id="cut-image" placeholder="https://..." /></label>
                        <label class="inline"><input id="cut-active" type="checkbox" checked />Ativo</label>
                        <button class="btn btn-primary" type="submit">Salvar Corte</button>
                    </form>
                </div>

                <div class="panel">
                    <h4>Cortes Cadastrados</h4>
                    <div id="admin-cuts"></div>
                </div>
            </div>

            <div class="admin-grid">
                <div class="panel">
                    <h4>Usuários</h4>
                    <div id="admin-users"></div>
                </div>
                <div class="panel">
                    <h4>Agendamentos</h4>
                    <div id="admin-appointments"></div>
                </div>
            </div>
        </section>
    </main>

    <script src="assets/js/admin.js"></script>
</body>
</html>
