# gord-o-cortes

Sistema web para barbearia com cardapio digital de cortes, agendamento online e painel administrativo para gerenciar servicos, precos, usuarios e horarios. Desenvolvido com PHP, JavaScript, CSS e MySQL, com layout moderno e responsivo.

## Estrutura principal
- `index.php` e `admin.php` (entradas web)
- `assets/css/style.css` (design responsivo)
- `assets/js/app.js` e `assets/js/admin.js` (frontend)
- `api.php` (entrada da API)
- `app/bootstrap.php` e `config/config.php` (backend e configuracao)
- `sql/database.sql` (script MySQL para Workbench)

## Como rodar
1. No MySQL Workbench, execute o conteudo de `sql/database.sql`.
2. Ajuste credenciais no arquivo `.env`.
3. Use `.env.example` como modelo, se precisar.
4. Rode com um servidor PHP local apontando para esta pasta.

## Admin inicial
- Email: `admin@gordaocortes.com`
- Senha: `admin123`

No primeiro login, a senha e convertida automaticamente para hash seguro.
