import api from './client';

export const authApi = {
  login: (email, password) => api.post('/auth/login', { email, password }),
  loginByCode: (code) => api.post('/auth/login-by-code', { code }),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
};

export const lojaApi = {
  dashboard: () => api.get('/loja/dashboard'),
};

export const produtoApi = {
  list: (params) => api.get('/loja/produtos', { params }),
  get: (id) => api.get(`/loja/produtos/${id}`),
  create: (data) => api.post('/loja/produtos', data),
  update: (id, data) => api.put(`/loja/produtos/${id}`, data),
  delete: (id) => api.delete(`/loja/produtos/${id}`),
  toggle: (id) => api.patch(`/loja/produtos/${id}/toggle`),
};

export const categoriaApi = {
  list: () => api.get('/loja/categorias'),
  get: (id) => api.get(`/loja/categorias/${id}`),
  create: (data) => api.post('/loja/categorias', data),
  update: (id, data) => api.put(`/loja/categorias/${id}`, data),
  delete: (id) => api.delete(`/loja/categorias/${id}`),
};

export const vendedorApi = {
  list: () => api.get('/loja/vendedores'),
  get: (id) => api.get(`/loja/vendedores/${id}`),
  create: (data) => api.post('/loja/vendedores', data),
  update: (id, data) => api.put(`/loja/vendedores/${id}`, data),
  delete: (id) => api.delete(`/loja/vendedores/${id}`),
  toggle: (id) => api.patch(`/loja/vendedores/${id}/toggle`),
};

export const encomendaApi = {
  list: (params) => api.get('/loja/encomendas', { params }),
  updateEstado: (id, estado) => api.patch(`/loja/encomendas/${id}/estado`, { estado }),
};

export const uploadApi = {
  imagem: (formData) =>
    api.post('/loja/upload/imagem', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
};
