## Teste para Desenvolvedor PHP/Laravel

Bem-vindo ao teste de desenvolvimento para a posição de Desenvolvedor PHP/Laravel.
Desenvolvedor : ### Julio Helena Neto
O objetivo deste teste é desenvolver uma API Rest para o cadastro de fornecedores, permitindo a busca por Nome, email ou Documento(CNPJ/CPF) utilizando Laravel no backend e um frontend em Vue.jsconsumindo essa API.


#### Instruções:
  - Clone o repositorio
  
  - Entre no diretorio `laravel` (cd laravel)

  - Rode o comando `composer install`

  # Copiar o arquivo de ambiente
    cp .env.example .env

    # Gerar a chave da aplicação
    php artisan key:generate

    # Iniciar os containers
    ./vendor/bin/sail up -d

    # Entrar no container docker do laravel
    docker exec -it <id-do-container-docker-do-laravel>  bash

    # Rodar as migrations e popular o banco de dados
    php artisan migrate:fresh --seed

    # Entre no diretorio front
    cd ../front

    # Rode o comando `yarn install`
    
    # Rode o comando `yarn dev`
    

    ### copie e cole ou digite no navegador `http://localhost:3000`

    - Deve exibir a lista dos fornecedores
    - Deve estar paginado de 10 em 10 registros
    - Deve conseguir ordenar por nome, email ou id
    - Para filtrar os fornecedores deve digitar no campo de pesquisa e pressionar enter, nome, email ou documento.
    - Para adicionar um novo fornecedor clique no botão `Novo Fornecedor`
    - Para editar um fornecedor clique no botão `Editar`
    - Para excluir um fornecedor clique no botão `Excluir`, uma mensagem de confirmação será exibida

    - Quando preencher o campo nos formularios de criacao ou edicao no campo documento ira fazer uma busca no BrasilAPI para validar o documento
    - Quando preencher o campo nos formularios de criacao ou edicao no campo cep  ira fazer uma busca no BrasilAPI para trazer os dados do endereço




