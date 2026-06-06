# Marketplace App — React Native (Expo)

App móvel para gerir a tua loja WhatsApp Marketplace.

## Pré-requisitos

- Node.js 18+
- Expo CLI: `npm install -g expo-cli`
- Expo Go no telemóvel (Android/iOS)

## Instalação

```bash
cd app-mobile
npm install
```

## Configuração

Edita `.env` com o URL do backend:

```
EXPO_PUBLIC_API_URL=http://localhost:8000
```

Para testar com telemóvel real, usa o IP local do PC:
```
EXPO_PUBLIC_API_URL=http://192.168.x.x:8000
```

## Executar

```bash
npx expo start
```

- Abre o Expo Go no telemóvel e escaneia o QR code
- Ou pressiona `a` para abrir no Android Emulator

## Screens

| Screen | Descrição |
|--------|-----------|
| Login | Autenticação com email/password |
| Dashboard | Estatísticas + últimas encomendas |
| Produtos | Lista com pesquisa e filtro por categoria |
| Novo Produto | Formulário com upload de imagem |
| Editar Produto | Editar/eliminar produto existente |
| Encomendas | Lista com filtro por estado + mudar estado |

## Estrutura

```
src/
├── api/           # Axios client + endpoints
├── components/    # StatCard, ProdutoCard, EncomendaCard, LoadingOverlay
├── context/       # AuthContext (token + user state)
├── navigation/    # AppNavigator (tabs + stacks)
└── screens/       # Todas as screens
```

## API

A app consome a API REST do Laravel com autenticação Sanctum.

- `POST /api/auth/login` → login
- `GET /api/loja/dashboard` → estatísticas
- `GET /api/loja/produtos` → lista de produtos
- `POST /api/loja/produtos` → criar produto
- `POST /api/loja/upload/imagem` → upload de imagem
