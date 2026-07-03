import React from 'react';
import { Alert } from 'react-native';
import ProdutoForm from '../components/ProdutoForm';
import { produtoApi } from '../api/endpoints';

export default function EditarProdutoScreen({ route, navigation }) {
  const { produto } = route.params;

  async function handleSave(data) {
    const res = await produtoApi.update(produto.id, data);
    if (!res.data.success) {
      throw new Error(res.data.message);
    }
    navigation.goBack();
  }

  function handleDelete() {
    Alert.alert('Confirmar', 'Eliminar este produto?', [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: async () => {
          try {
            await produtoApi.delete(produto.id);
            navigation.goBack();
          } catch {
            Alert.alert('Erro', 'Não foi possível eliminar.');
          }
        },
      },
    ]);
  }

  return (
    <ProdutoForm
      initialData={produto}
      onSubmit={handleSave}
      onDelete={handleDelete}
      submitLabel="Actualizar"
      successMessage="Produto actualizado!"
    />
  );
}
