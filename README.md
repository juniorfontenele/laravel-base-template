# Laravel Base Template

Template base para Laravel com as seguintes funcionalidades:

- Logging com contexto
- Rastreabilidade de requests, sessões e usuários
- Rate-limiting de erros e requisições
- CI/CD
- Ferramentas de Debug (Laradumps, Debugbar, XDebug)
- Ferramentas de qualidade de código (Rector, Pint, PHPStan)
- Ferramentas de testes automatizados (Pest, PHPUnit)
- Ambiente de desenvolvimento com Solo e Devcontainer
- Template React + Typescript + Inertia

## Instalação

Crie um novo projeto do composer com o comando:

```bash
composer create-project <repositório> <diretório_destino>
```

Após a criação do projeto, entre no diretório criado

```bash
cd <diretório_destino>
```

Abra o VSCode na pasta do projeto
Dentro do VSCode, abra o devcontainer
Após a inicialização do devcontainer, abra o terminal e digite o comando:

```bash
php artisan app:init --seed
```

**OBS:** Caso não utilize o devcontainer, rode os seguintes comandos:

```bash
composer install
composer run init-project
```

Caso necessite inicializar o servidor web, digite o comando abaixo no terminal:

```bash
php artisan solo
```

O comando irá inicializar o servidor Web na porta 8000 e o servidor de desenvolvimento de assets Vite na porta 5173. 

Para acessar o servidor Web, abra o navegador no endereço `http://localhost:8000/`

## Testes

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Junior Fontenele](https://github.com/juniorfontenele)

## License

Proprietary. Please see [License File](LICENSE.md) for more information.
