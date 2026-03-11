<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gordão Cortes</title>
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
                <h1>Gordão Cortes</h1>
                <p>Seu estilo começa aqui</p>
            </div>
        </div>

        <nav>
            <a href="#cardapio">Cardápio</a>
            <a href="#agendar">Agendar</a>
            <a href="admin.php">Área Admin</a>
        </nav>

        <div class="header-actions">
            <button id="auth-open" class="btn btn-primary">Login</button>
            <button id="logout-btn" class="btn btn-outline hidden">Sair</button>
        </div>
    </header>

    <main>
        <section class="hero">
            <div>
                <h2>Cardápio de Cortes + Agendamento Online</h2>
                <p>Veja os cortes, escolha o estilo e reserve seu horário em poucos segundos.</p>
                <a href="#cardapio" class="btn btn-primary">Ver cortes</a>
            </div>
            <div class="hero-card">
                <p>Aberto de segunda a sábado</p>
                <strong>08:00 - 20:00</strong>
                <small>Rua Exemplo, 123 - Centro</small>
            </div>
        </section>

        <section id="cardapio" class="section">
            <div class="section-head">
                <h3>Cardápio de Cortes</h3>
                <p>Escolha o melhor estilo pra você.</p>
            </div>
            <div id="cuts-grid" class="cuts-grid"></div>
        </section>

        <section id="agendar" class="section split">
            <div>
                <h3>Marcar Horário</h3>
                <p id="booking-message">Faça login para agendar seu horário.</p>
                <form id="booking-form" class="panel hidden">
                    <label>Corte
                        <select id="booking-cut" required></select>
                    </label>
                    <label>Data
                        <input type="date" id="booking-date" required />
                    </label>
                    <label>Horário
                        <select id="booking-time" required></select>
                    </label>
                    <label>Observações
                        <textarea id="booking-notes" rows="3" placeholder="Opcional"></textarea>
                    </label>
                    <button class="btn btn-primary" type="submit">Confirmar Agendamento</button>
                </form>
            </div>

            <div>
                <h3>Meus Agendamentos</h3>
                <div id="appointments" class="panel"></div>
            </div>
        </section>
    </main>

    <div id="auth-modal" class="modal hidden">
        <div class="modal-box">
            <button id="auth-close" class="close">x</button>
            <h3 id="auth-title">Entrar</h3>
            <form id="auth-form" class="tight-form">
                <label id="auth-name-wrap" class="hidden">Nome
                    <input id="auth-name" placeholder="Seu nome" />
                </label>
                <label>Email
                    <input id="auth-email" type="email" required />
                </label>
                <label>Senha
                    <input id="auth-password" type="password" required minlength="6" />
                </label>
                <button type="submit" id="auth-submit" class="btn btn-primary">Entrar</button>
            </form>
            <div class="auth-switch">
                <button type="button" id="mode-login" class="btn btn-outline">Já tenho conta</button>
                <button type="button" id="mode-register" class="btn btn-outline">Quero me cadastrar</button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
