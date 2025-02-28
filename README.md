# Documentação da API de Consulta de CEP

## Visão Geral

Esta API permite a consulta de logradouros (ruas), cidades e estados com base em parâmetros fornecidos pelos usuários. O acesso é protegido por uma chave de API.

## Autenticação

A API requer autenticação via chave de API (`api_key`), que deve ser enviada no corpo da requisição. Caso a chave seja inválida ou expirada, a API responderá com um erro 403.

### Exemplo de Requisição

```json
{
    "api_key": "SUA_CHAVE_AQUI",
    "action": "buscaRua",
    "cidade": "Belo Horizonte",
    "estado": "MG",
    "rua": "Afonso Pena"
}
```

## Endpoints

### `buscaRua`

Busca informações sobre uma rua com base na cidade e no estado informados.

#### Parâmetros:

- `rua` (string) - **Obrigatório**. Nome da rua a ser pesquisada.
- `api_key` (string) - **Obrigatório**. Chave de autenticação do usuário.
- `cidade` (string) - Opcional. Nome da cidade.
- `estado` (string) - Opcional. Sigla do estado (ex: "MG").

#### Resposta de Sucesso (200):

```json
[
    {
        "rua": "Avenida Afonso Pena",
        "bairro": "Centro",
        "cidade": "Belo Horizonte",
        "estado": "MG",
        "cep": "30130-000"
    }
]
```

#### Resposta de Erro (404):

```json
{
    "mensagem": "Nenhuma cidade encontrada"
}
```

### `buscaEstado`

Retorna o estado correspondente a uma cidade informada.

#### Parâmetros:

- `cidade` (string) - Nome da cidade a ser pesquisada

#### Resposta de Sucesso (200):

```json
[
    {
        "Cidade": "Belo Horizonte",
        "Uf": "MG"
    }
]
```

#### Resposta de Erro (404):

```json
{
    "mensagem": "Cidade não encontrada"
}
```

## Códigos de Status

- `200 OK` - Requisição bem-sucedida
- `403 Forbidden` - Chave de API inválida ou expirada
- `404 Not Found` - Nenhuma informação encontrada

## Considerações Finais

Certifique-se de sempre enviar a chave de API válida e formatar corretamente os parâmetros nas requisições para evitar erros. Apenas os parâmetros `rua` e `api_key` são obrigatórios; os demais são opcionais.

