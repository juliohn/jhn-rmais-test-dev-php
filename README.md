# Teste para Desenvolvedor PHP/Laravel

Bem-vindo ao teste de desenvolvimento para a posição de Desenvolvedor PHP/Laravel.

**Desenvolvedor:** Julio Helena Neto

## Descrição
O objetivo deste teste é desenvolver uma API Rest para o cadastro de fornecedores, permitindo a busca por Nome, email ou Documento (CNPJ/CPF) utilizando Laravel no backend e um frontend em Vue.js consumindo essa API.

## Instruções

1. Clone o repositório

2. Entre no diretório `laravel`:
- cd laravel

3. Instale as dependências:
- cd composer install

4. Copiar o arquivo de ambiente
  - cp .env.example .env

5. Gerar a chave da aplicação
  - php artisan key:generate

6. Iniciar os containers
  - ./vendor/bin/sail up -d

7. Entrar no container docker do laravel
  - docker exec -it (id-do-container-docker-do-laravel) bash

8. Rodar as migrations e popular o banco de dados
  - php artisan migrate:fresh --seed

9. Configure o frontend:
  - cd ../front
  - yarn install
  - yarn dev

10. Acesse no navegador: `http://localhost:3000`

## Funcionalidades

- Deve exibir a lista dos fornecedores
- Deve exibir o detalhe de um fornecedor ao clicar na linha da lista
- Deve estar paginado de 10 em 10 registros
- Deve conseguir ordenar por nome, email ou id
- Para filtrar os fornecedores deve digitar no campo de pesquisa e pressionar enter, nome, email ou documento
- Para adicionar um novo fornecedor clique no botão `Novo Fornecedor`
- Para editar um fornecedor clique no botão `Editar`
- Para excluir um fornecedor clique no botão `Excluir`, uma mensagem de confirmação será exibida

## Recursos Especiais

- Quando preencher o campo nos formulários de criação ou edição no campo documento irá fazer uma busca no BrasilAPI para validar o documento
- Quando preencher o campo nos formulários de criação ou edição no campo cep irá fazer uma busca no BrasilAPI para trazer os dados do endereço
