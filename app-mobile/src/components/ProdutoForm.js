import React, { useState, useEffect } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, ScrollView,
  Alert, Image, Switch,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { categoriaApi, uploadApi } from '../api/endpoints';
import { TAMANHOS_PREDEFINIDOS } from '../constants/estados';
import { sharedStyles } from '../styles/theme';

export default function ProdutoForm({
  initialData,
  onSubmit,
  onDelete,
  submitLabel = 'Guardar Produto',
  successMessage = 'Guardado com sucesso!',
}) {
  const [nome, setNome] = useState(initialData?.nome || '');
  const [descricao, setDescricao] = useState(initialData?.descricao || '');
  const [preco, setPreco] = useState(String(initialData?.preco || ''));
  const [stock, setStock] = useState(String(initialData?.stock || ''));
  const [stockMinimo, setStockMinimo] = useState(String(initialData?.stock_minimo || '5'));
  const [custoUnitario, setCustoUnitario] = useState(String(initialData?.custo_unitario || ''));
  const [unidade, setUnidade] = useState(initialData?.unidade || 'unidade');
  const [categoriaId, setCategoriaId] = useState(initialData?.categoria_id || null);
  const [vendedorId, setVendedorId] = useState(initialData?.vendedor_id || null);
  const [disponivel, setDisponivel] = useState(initialData?.disponivel ?? true);
  const [destaque, setDestaque] = useState(initialData?.destaque ?? false);
  const [imagem, setImagem] = useState(null);
  const [imagem2, setImagem2] = useState(null);
  const [imagemUrl, setImagemUrl] = useState(initialData?.imagem_url || null);
  const [imagem2Url, setImagem2Url] = useState(initialData?.imagem2_url || null);
  const [categorias, setCategorias] = useState([]);
  const [loading, setLoading] = useState(false);
  const [cores, setCores] = useState(initialData?.cores || []);
  const [novaCor, setNovaCor] = useState('');
  const [tamanhos, setTamanhos] = useState(initialData?.tamanhos || []);

  useEffect(() => {
    categoriaApi.list().then((res) => {
      if (res.data.success) setCategorias(res.data.data);
    }).catch(() => {});
  }, []);

  async function pickImage(isSecond) {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: 'Images',
      allowsEditing: true,
      aspect: [4, 3],
      quality: 0.8,
    });
    if (!result.canceled) {
      if (isSecond) {
        setImagem2(result.assets[0]);
      } else {
        setImagem(result.assets[0]);
      }
    }
  }

  function removeImage(isSecond) {
    if (isSecond) {
      setImagem2(null);
      setImagem2Url(null);
    } else {
      setImagem(null);
      setImagemUrl(null);
    }
  }

  function adicionarCor() {
    const cor = novaCor.trim();
    if (!cor) return;
    if (cores.includes(cor)) {
      Alert.alert('Aviso', 'Essa cor já foi adicionada.');
      return;
    }
    if (cores.length >= 10) {
      Alert.alert('Aviso', 'Máximo de 10 cores.');
      return;
    }
    setCores([...cores, cor]);
    setNovaCor('');
  }

  function removerCor(index) {
    setCores(cores.filter((_, i) => i !== index));
  }

  function toggleTamanho(t) {
    if (tamanhos.includes(t)) {
      setTamanhos(tamanhos.filter((x) => x !== t));
    } else {
      if (tamanhos.length >= 10) {
        Alert.alert('Aviso', 'Máximo de 10 tamanhos.');
        return;
      }
      setTamanhos([...tamanhos, t]);
    }
  }

  async function uploadImage(imageAsset) {
    const formData = new FormData();
    formData.append('imagem', {
      uri: imageAsset.uri,
      type: 'image/jpeg',
      name: 'produto.jpg',
    });
    const uploadRes = await uploadApi.imagem(formData);
    if (uploadRes.data.success) {
      return uploadRes.data.data.url;
    }
    return null;
  }

  async function handleSave() {
    if (!nome || !preco || !stock || !categoriaId) {
      Alert.alert('Erro', 'Preenche nome, preço, stock e categoria.');
      return;
    }
    if (Number(preco) <= 0) {
      Alert.alert('Erro', 'O preço deve ser maior que 0.');
      return;
    }
    if (!imagem && !imagemUrl) {
      Alert.alert('Erro', 'A foto da frente é obrigatória.');
      return;
    }

    setLoading(true);
    try {
      let finalImageUrl = imagemUrl;
      let finalImageUrl2 = imagem2Url;

      if (imagem) {
        finalImageUrl = await uploadImage(imagem);
      }
      if (imagem2) {
        finalImageUrl2 = await uploadImage(imagem2);
      }

      const data = {
        nome,
        descricao,
        preco: Number(preco),
        stock: Number(stock),
        stock_minimo: Number(stockMinimo) || 5,
        custo_unitario: Number(custoUnitario) || 0,
        unidade,
        categoria_id: categoriaId,
        vendedor_id: vendedorId,
        disponivel,
        destaque,
        imagem_url: finalImageUrl,
        imagem2_url: finalImageUrl2,
        cores: cores.length > 0 ? cores : null,
        tamanhos: tamanhos.length > 0 ? tamanhos : null,
      };

      await onSubmit(data);
      Alert.alert('Sucesso', successMessage);
    } catch (err) {
      const msg = err.response?.data?.message || 'Erro ao guardar.';
      Alert.alert('Erro', msg);
    }
    setLoading(false);
  }

  return (
    <ScrollView style={sharedStyles.container} contentContainerStyle={sharedStyles.scrollContent}>
      <Text style={sharedStyles.label}>Nome *</Text>
      <TextInput style={sharedStyles.input} value={nome} onChangeText={setNome} placeholder="Nome do produto" />

      <Text style={sharedStyles.label}>Descrição</Text>
      <TextInput
        style={[sharedStyles.input, sharedStyles.inputMultiline]}
        value={descricao}
        onChangeText={setDescricao}
        placeholder="Descrição do produto"
        multiline
      />

      <View style={sharedStyles.row}>
        <View style={{ flex: 1 }}>
          <Text style={sharedStyles.label}>Preço (MZN) *</Text>
          <TextInput
            style={sharedStyles.input}
            value={preco}
            onChangeText={setPreco}
            keyboardType="numeric"
            placeholder="0"
          />
        </View>
        <View style={{ flex: 1, marginLeft: 8 }}>
          <Text style={sharedStyles.label}>Stock *</Text>
          <TextInput
            style={sharedStyles.input}
            value={stock}
            onChangeText={setStock}
            keyboardType="numeric"
            placeholder="0"
          />
        </View>
      </View>

      <View style={sharedStyles.row}>
        <View style={{ flex: 1 }}>
          <Text style={sharedStyles.label}>Stock Mínimo</Text>
          <TextInput
            style={sharedStyles.input}
            value={stockMinimo}
            onChangeText={setStockMinimo}
            keyboardType="numeric"
            placeholder="5"
          />
        </View>
        <View style={{ flex: 1, marginLeft: 8 }}>
          <Text style={sharedStyles.label}>Custo Unitário (MZN)</Text>
          <TextInput
            style={sharedStyles.input}
            value={custoUnitario}
            onChangeText={setCustoUnitario}
            keyboardType="numeric"
            placeholder="0"
          />
        </View>
      </View>

      <Text style={sharedStyles.label}>Unidade</Text>
      <View style={sharedStyles.chipsRow}>
        {['unidade', 'kg', 'litro', 'caixa'].map((u) => (
          <TouchableOpacity
            key={u}
            style={[sharedStyles.tamanhoToggle, unidade === u && sharedStyles.tamanhoToggleActive]}
            onPress={() => setUnidade(u)}
          >
            <Text style={[sharedStyles.tamanhoText, unidade === u && sharedStyles.tamanhoTextActive]}>
              {u === 'unidade' ? 'Unidade' : u === 'kg' ? 'Kg' : u === 'litro' ? 'Litro' : 'Caixa'}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      <Text style={sharedStyles.label}>Categoria *</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 12 }}>
        {categorias.map((cat) => (
          <TouchableOpacity
            key={cat.id}
            style={[sharedStyles.catChip, categoriaId === cat.id && sharedStyles.catChipActive]}
            onPress={() => setCategoriaId(cat.id)}
          >
            <Text style={[sharedStyles.catChipText, categoriaId === cat.id && sharedStyles.catChipTextActive]}>
              {cat.icone ? `${cat.icone} ` : ''}{cat.nome}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <Text style={sharedStyles.label}>Foto frente *</Text>
      <View style={sharedStyles.imageContainer}>
        <TouchableOpacity style={sharedStyles.imageBtn} onPress={() => pickImage(false)}>
          {imagem ? (
            <Image source={{ uri: imagem.uri }} style={sharedStyles.imagePreview} />
          ) : imagemUrl ? (
            <Image source={{ uri: imagemUrl }} style={sharedStyles.imagePreview} />
          ) : (
            <Text style={sharedStyles.imageBtnText}>Escolher da galeria</Text>
          )}
        </TouchableOpacity>
        {(imagem || imagemUrl) && (
          <TouchableOpacity style={sharedStyles.removeBtn} onPress={() => removeImage(false)}>
            <Text style={sharedStyles.removeBtnText}>x</Text>
          </TouchableOpacity>
        )}
      </View>

      <Text style={sharedStyles.label}>Foto trás (opcional)</Text>
      <View style={sharedStyles.imageContainer}>
        <TouchableOpacity style={sharedStyles.imageBtn} onPress={() => pickImage(true)}>
          {imagem2 ? (
            <Image source={{ uri: imagem2.uri }} style={sharedStyles.imagePreview} />
          ) : imagem2Url ? (
            <Image source={{ uri: imagem2Url }} style={sharedStyles.imagePreview} />
          ) : (
            <Text style={sharedStyles.imageBtnText}>Escolher da galeria</Text>
          )}
        </TouchableOpacity>
        {(imagem2 || imagem2Url) && (
          <TouchableOpacity style={sharedStyles.removeBtn} onPress={() => removeImage(true)}>
            <Text style={sharedStyles.removeBtnText}>x</Text>
          </TouchableOpacity>
        )}
      </View>

      <View style={sharedStyles.rowBetween}>
        <Text style={sharedStyles.label}>Disponível</Text>
        <Switch value={disponivel} onValueChange={setDisponivel} />
      </View>
      <View style={sharedStyles.rowBetween}>
        <Text style={sharedStyles.label}>Destaque</Text>
        <Switch value={destaque} onValueChange={setDestaque} />
      </View>

      <View style={sharedStyles.variantesContainer}>
        <Text style={sharedStyles.variantesTitle}>Variantes (opcional)</Text>

        <Text style={sharedStyles.label}>Cores disponíveis</Text>
        <View style={sharedStyles.chipsRow}>
          {cores.map((cor, i) => (
            <View key={i} style={sharedStyles.chip}>
              <Text style={sharedStyles.chipText}>{cor}</Text>
              <TouchableOpacity onPress={() => removerCor(i)}>
                <Text style={sharedStyles.chipRemove}>x</Text>
              </TouchableOpacity>
            </View>
          ))}
        </View>
        <View style={sharedStyles.inputRow}>
          <TextInput
            style={[sharedStyles.input, { flex: 1 }]}
            value={novaCor}
            onChangeText={setNovaCor}
            placeholder="Nova cor..."
            onSubmitEditing={adicionarCor}
          />
          <TouchableOpacity style={sharedStyles.addBtn} onPress={adicionarCor}>
            <Text style={sharedStyles.addBtnText}>+</Text>
          </TouchableOpacity>
        </View>

        <Text style={[sharedStyles.label, { marginTop: 12 }]}>Tamanhos disponíveis</Text>
        <View style={sharedStyles.chipsRow}>
          {TAMANHOS_PREDEFINIDOS.map((t) => (
            <TouchableOpacity
              key={t}
              style={[sharedStyles.tamanhoToggle, tamanhos.includes(t) && sharedStyles.tamanhoToggleActive]}
              onPress={() => toggleTamanho(t)}
            >
              <Text style={[sharedStyles.tamanhoText, tamanhos.includes(t) && sharedStyles.tamanhoTextActive]}>
                {t}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      </View>

      <TouchableOpacity
        style={[sharedStyles.saveBtn, loading && { opacity: 0.6 }]}
        onPress={handleSave}
        disabled={loading}
      >
        <Text style={sharedStyles.saveBtnText}>{loading ? 'Guardando...' : submitLabel}</Text>
      </TouchableOpacity>

      {onDelete && (
        <TouchableOpacity style={sharedStyles.deleteBtn} onPress={onDelete}>
          <Text style={sharedStyles.deleteBtnText}>Eliminar Produto</Text>
        </TouchableOpacity>
      )}
    </ScrollView>
  );
}
