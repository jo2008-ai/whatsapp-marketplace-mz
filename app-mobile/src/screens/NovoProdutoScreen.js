import React from 'react';
import { Alert } from 'react-native';
import ProdutoForm from '../components/ProdutoForm';
import { produtoApi } from '../api/endpoints';

export default function NovoProdutoScreen({ navigation }) {
  async function handleSave(data) {
    const res = await produtoApi.create(data);
    if (!res.data.success) {
      throw new Error(res.data.message);
    }
    navigation.goBack();
  }

  return (
    <ProdutoForm
      onSubmit={handleSave}
      submitLabel="Guardar Produto"
      successMessage="Produto criado!"
    />
  );
}
