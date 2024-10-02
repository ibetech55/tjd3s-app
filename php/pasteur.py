#!/usr/bin/env python3
import cv2
import os
from mtcnn import MTCNN
import numpy as np
import shutil
from cryptography.fernet import Fernet
from datetime import datetime, timezone
import time
import argparse
import json

# Início da contagem do tempo
start_time = time.time()

# Diretórios de criptografia
source_dir = "../imagem/input"
dest_dir = "../imagem/cript"
key_dir = "../imagem/key"

a = 0 # variável global
novo_nome_arquivo = ""
caminho_arquivo_anonimizado = ""
caminho_arquivo_original = ""
base_dir = '/imagem'

# cria as pastas necessárias
def ensure_directory_exists(directory_path):
    # Verifica se a pasta existe
    if not os.path.exists(directory_path):
        # Se não existir, cria a pasta
        os.makedirs(directory_path)
        print(f"Pasta '{directory_path}' criada.")
    else:
        print(f"Pasta '{directory_path}' já existe.")
folder_path = '../imagem/cript'
ensure_directory_exists(folder_path)
folder_path = '../imagem/pasteur1'
ensure_directory_exists(folder_path)
folder_path = '../imagem/pasteur2'
ensure_directory_exists(folder_path)
folder_path = '../imagem/key'
ensure_directory_exists(folder_path)
folder_path = '../imagem/decrypted'
ensure_directory_exists(folder_path)
folder_path = '../imagem/lixeira'
ensure_directory_exists(folder_path)

# Diretórios de origem e destino da lixeira
origem = '../imagem/input'
#origem1 = '../imagem/input'
#origem2 = '../imagem/pasteur'
destino = '../imagem/lixeira'

############# mudar nomes
# Obter a data e hora atual em UTC
agora_utc = datetime.now(timezone.utc)

# Formatar a data e hora como string
data_hora_string = agora_utc.strftime('%Y_%m_%d_%H_%M_%S')

# Diretório contendo os arquivos de imagem
diretorio = '../imagem/input'

# Extensões de arquivos de imagem que você deseja renomear
extensoes_imagem = ['.jpg', '.jpeg', '.png', '.gif', '.bmp']

# Renomear todos os arquivos de imagem na pasta
for arquivo in os.listdir(diretorio):
    caminho_arquivo = os.path.join(diretorio, arquivo)
    
    # Verifique se é um arquivo e se tem uma das extensões de imagem
    if os.path.isfile(caminho_arquivo) and any(arquivo.lower().endswith(ext) for ext in extensoes_imagem):
        nome, extensao = os.path.splitext(arquivo)
        novo_nome_arquivo = f"{nome}_{data_hora_string}{extensao}"
        caminho_arquivo_novo = os.path.join(diretorio, novo_nome_arquivo)
        
        # Renomear o arquivo
        os.rename(caminho_arquivo, caminho_arquivo_novo)

print("Arquivos de imagem renomeados com sucesso!")

#####################################
# método Haar Cascade para detecção de faces
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_alt.xml')

# Diretório contendo os arquivos de imagem
diretorio_imagens = '../imagem/pasteur1'

# Criar um diretório para salvar as imagens com rostos detectados, se não existir
diretorio_saida = '../imagem/pasteur2'
if not os.path.exists(diretorio_saida):
    os.makedirs(diretorio_saida)
#def remove_faces_cascade():
#    global a
# Processar cada imagem no diretório
#    for arquivo in os.listdir(diretorio_imagens):
#        caminho_imagem = os.path.join(diretorio_imagens, arquivo)
#
#        # Verifique se é um arquivo de imagem
#        if os.path.isfile(caminho_imagem) and arquivo.lower().endswith(('.jpg', '.jpeg', '.png', '.bmp', '.gif')):
#            # Carregar a imagem
#            imagem = cv2.imread(caminho_imagem)
#        
#            # Converter a imagem para escala de cinza
#           # imagem_cinza = cv2.cvtColor(imagem, cv2.COLOR_BGR2GRAY)
#        
#            # Detectar rostos na imagem
#            rostos = face_cascade.detectMultiScale(imagem, scaleFactor=1.09, minNeighbors=4, minSize=(10, 10))
#            
#            # Desenhar retângulos ao redor dos rostos detectados
#            for (x, y, w, h) in rostos:
#                cv2.rectangle(imagem, (x, y), (x+w, y+h), (0, 0, 0), -1)
#            # Salvar a imagem com os rostos detectados no diretório de saída
#            caminho_saida = os.path.join(diretorio_saida, arquivo)
#            cv2.imwrite(caminho_saida, imagem)
#
#    print("Processamento concluído! Imagens com rostos detectados salvas em:", diretorio_saida)
#####################################
# função para remover faces método MTCNN
def remove_faces_from_image(image_path, output_dir):
    global a
    a = 0
    global caminho_arquivo_anonimizado
    caminho_arquivo_anonimizado = ""

    # Carregar a imagem
    image = cv2.imread(image_path)
    if image is None:
        print(f"Erro ao carregar a imagem: {image_path}")
        return
   ##############
    # Carregar a imagem
    image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

    # Equalização de histograma
    image_yuv = cv2.cvtColor(image_rgb, cv2.COLOR_RGB2YUV)
    image_yuv[:, :, 0] = cv2.equalizeHist(image_yuv[:, :, 0])
    image_eq = cv2.cvtColor(image_yuv, cv2.COLOR_YUV2RGB)

     # Correção Gama
    gamma = 1.1  # ajuste o valor de gamma conforme necessário
    image_gamma = np.power(image_eq/255.0, gamma)
    image_gamma = np.uint8(image_gamma*255)

    # Normalização da iluminação
    image_norm = cv2.normalize(image_gamma, None, 0, 255, cv2.NORM_MINMAX)
    image = image_norm
    ##############
    # Inicializar o detector de rostos MTCNN
    detector = MTCNN(min_face_size=10, scale_factor=0.6, steps_threshold=[0.4, 0.4, 0.4])

    # Detectar rostos na imagem
    results = detector.detect_faces(image)

    # Criar uma máscara para cobrir os rostos
    mask = np.ones_like(image, dtype=np.uint8) * 255  # Máscara branca
   
    # Aplicar a máscara na imagem original e deixa preto e branco
    for result in results:
        x, y, width, height = result['box']
        x, y = abs(x), abs(y)
        # Cobrir o rosto com uma máscara preta
        mask[y:y + height, x:x + width] = 0
        a= a+1
        font = cv2.FONT_HERSHEY_SIMPLEX
        org = (x, y)
        fontScale = 0.7
        color = (0, 0, 0)
        thickness = 1
        cv2.putText(image, str(a), org, font,fontScale, color, thickness, cv2.LINE_AA)
    # Definir as coordenadas do retângulo (ponto inicial e final)
    ponto_inicial = (4, 10)   # (x1, y1)
    ponto_final = (110, 30)   # (x2, y2)

    # Cor do retângulo (branco no formato BGR)
    cor_branca = (255, 255, 255)

    # Espessura da borda (se for -1, o retângulo será preenchido)
    espessura = -1

    # Desenhar o retângulo na imagem
    cv2.rectangle(image, ponto_inicial, ponto_final, cor_branca, espessura)
    cv2.rectangle(image, ponto_inicial, ponto_final, (0, 0, 0), 1)
    # exibe o numero de pessoas
    cv2.putText(image, str(a)+ " pessoas", (5, 25), font, 0.5, (0, 0, 0), 1, cv2.LINE_AA)
    
    # Aplicar a máscara na imagem original e deixa preto e branco
    image_without_faces = cv2.bitwise_and(image, mask)
    image_without_faces = cv2.cvtColor(image_without_faces, cv2.COLOR_BGR2GRAY)
    # Criar a pasta de saída, se não existir
    os.makedirs(output_dir, exist_ok=True)

    # Gerar o caminho completo para salvar a nova imagem
    output_path = os.path.join(output_dir, os.path.basename(image_path))
    # Salvar a imagem sem rostos
    cv2.imwrite(output_path, image_without_faces)
    caminho_arquivo_anonimizado = os.path.join(base_dir, 'pasteur1', os.path.basename(image_path))

    print(f"Imagem salva em: {output_path}")

#processa todas as imagens do diretorio input    
def process_images(input_dir, output_dir):
    # Verificar se a pasta de entrada existe
    if not os.path.exists(input_dir):
        print(f"A pasta de entrada {input_dir} não existe.")
        return

    # Processar cada arquivo na pasta de entrada
    for filename in os.listdir(input_dir):
        if filename.endswith(('.png', '.jpg', '.jpeg')):
            image_path = os.path.join(input_dir, filename)
            remove_faces_from_image(image_path, output_dir)

input_dir = "../imagem/input"
output_dir = "../imagem/pasteur1"
process_images(input_dir, output_dir)
#remove_faces_cascade()
# criptografia de imagem e geração de key
def generate_key():
    """Gera uma chave para a criptografia"""
    return Fernet.generate_key()

def encrypt_image(file_path, key):
    """Criptografa a imagem e retorna os dados criptografados"""
    with open(file_path, 'rb') as file:
        image_data = file.read()
    fernet = Fernet(key)
    encrypted_data = fernet.encrypt(image_data)
    return encrypted_data

def save_encrypted_image(encrypted_data, dest_path):
    """Salva os dados criptografados em um arquivo"""
    with open(dest_path, 'wb') as file:
        file.write(encrypted_data)

def save_key(key, key_path):
    """Salva a chave de criptografia em um arquivo"""
    with open(key_path, 'wb') as file:
        file.write(key)

def process_images():
    """Processa todas as imagens na pasta de origem"""
    for filename in os.listdir(source_dir):
        if filename.endswith(('.png', '.jpg', '.jpeg', '.bmp', '.gif')):
            file_path = os.path.join(source_dir, filename)
            encrypted_file_path = os.path.join(dest_dir, filename + ".enc")
            key_file_path = os.path.join(key_dir, filename + ".key")

            # Gera a chave e criptografa a imagem
            key = generate_key()
            encrypted_data = encrypt_image(file_path, key)

            # Salva a imagem criptografada e a chave
            save_encrypted_image(encrypted_data, encrypted_file_path)
            save_key(key, key_file_path)

            print(f"Imagem '{filename}' criptografada com sucesso!")

if __name__ == "__main__":
    process_images()

# transfere os arquivos input para a lixeira
for arquivo in os.listdir(origem):
    caminho_arquivo_origem = os.path.join(origem, arquivo)
    caminho_arquivo_destino = os.path.join(destino, arquivo)
    
    # Verifique se é um arquivo e não um diretório
    if os.path.isfile(caminho_arquivo_origem):
        shutil.move(caminho_arquivo_origem, caminho_arquivo_destino)
        caminho_arquivo_original = os.path.join(base_dir, 'lixeira', os.path.basename(arquivo))
print("Todos os arquivos foram movidos com sucesso!")

# Fim da contagem do tempo
end_time = time.time()

# Tempo gasto
execution_time = end_time - start_time
print(f"Tempo de execução: {execution_time:.4f} segundos")

# After processing images, create a JSON output
output_data = {"quantidade_pessoas": a, "caminho_arquivo_anonimizado":caminho_arquivo_anonimizado, "caminho_arquivo_original":caminho_arquivo_original, "nome_arquivo":novo_nome_arquivo}

# Save to output.json
with open('output.json', 'w') as f:
    json.dump(output_data, f)

### Executavel: no anaconda ir para o diretorio cd ../imagem
### executar o comando pyinstaller --onefile --distpath ../imagem CTCNN_fundacentro_07_09_2024.py
# auto-py-to-exe
## (base) C:\imagem\fundacentro>cxfreeze setup.py --target-dir fundacentro