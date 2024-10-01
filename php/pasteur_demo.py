#!/usr/bin/env python3
print("Python script started")

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

# Argument parser
parser = argparse.ArgumentParser(description="Process images for face detection and encryption.")
parser.add_argument("input_file", help="Path to the input image file or directory.")
args = parser.parse_args()

# Ensure the input file is in the ../input directory
source_dir = "/imagem/input"
dest_dir = "/imagem/cript"
key_dir = "/imagem/key"



# cria as pastas necessárias
def ensure_directory_exists(directory_path):
    if not os.path.exists(directory_path):
        os.makedirs(directory_path)
        print(f"Pasta '{directory_path}' criada.")
    else:
        print(f"Pasta '{directory_path}' já existe.")

# Ensure all necessary directories exist
directories = ['/imagem/cript', '/imagem/pasteur1', '/imagem/pasteur2', '/imagem/key', '/imagem/decrypted', '/imagem/lixeira']
for folder in directories:
    ensure_directory_exists(f"/{folder}")

# Diretórios de origem e destino da lixeira
origem = '/imagem/input'
#origem1 = '/imagem/input'
#origem2 = '/imagem/pasteur'
destino = '/imagem/lixeira'

# Obter a data e hora atual em UTC
agora_utc = datetime.now(timezone.utc)
data_hora_string = agora_utc.strftime('%Y_%m_%d_%H_%M_%S')


# Renomear arquivos de imagem na pasta de entrada
global novo_nome_arquivo
for arquivo in os.listdir(source_dir):
    caminho_arquivo = os.path.join(source_dir, arquivo)
    
    if os.path.isfile(caminho_arquivo) and any(arquivo.lower().endswith(ext) for ext in ['.jpg', '.jpeg', '.png', '.gif', '.bmp']):
        nome, extensao = os.path.splitext(arquivo)
        novo_nome_arquivo = f"{nome}_{data_hora_string}{extensao}"
        caminho_arquivo_novo = os.path.join(source_dir, novo_nome_arquivo)
        
        os.rename(caminho_arquivo, caminho_arquivo_novo)

print("Arquivos de imagem renomeados com sucesso!")

# Método Haar Cascade para detecção de faces
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_alt.xml')

# Criar um diretório para salvar as imagens com rostos detectados, se não existir
diretorio_saida = '/imagem/pasteur2'
if not os.path.exists(diretorio_saida):
    os.makedirs(diretorio_saida)

# Função para remover faces método MTCNN
def remove_faces_from_image(image_path, output_dir):
    global a
    global output_path

    image = cv2.imread(image_path)
    if image is None:
        print(f"Erro ao carregar a imagem: {image_path}")
        return

    image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    image_yuv = cv2.cvtColor(image_rgb, cv2.COLOR_RGB2YUV)
    image_yuv[:, :, 0] = cv2.equalizeHist(image_yuv[:, :, 0])
    image_eq = cv2.cvtColor(image_yuv, cv2.COLOR_YUV2RGB)

    gamma = 1.1
    image_gamma = np.power(image_eq / 255.0, gamma)
    image_gamma = np.uint8(image_gamma * 255)

    image_norm = cv2.normalize(image_gamma, None, 0, 255, cv2.NORM_MINMAX)
    image = image_norm

    detector = MTCNN(min_face_size=10, scale_factor=0.6, steps_threshold=[0.4, 0.4, 0.4])
    results = detector.detect_faces(image)

    mask = np.ones_like(image, dtype=np.uint8) * 255

    for result in results:
        x, y, width, height = result['box']
        x, y = abs(x), abs(y)
        mask[y:y + height, x:x + width] = 0
        a += 1
        font = cv2.FONT_HERSHEY_SIMPLEX
        org = (x, y)
        fontScale = 0.7
        color = (0, 0, 0)
        thickness = 1
        cv2.putText(image, str(a), org, font, fontScale, color, thickness, cv2.LINE_AA)

    ponto_inicial = (4, 10)
    ponto_final = (110, 30)
    cor_branca = (255, 255, 255)
    espessura = -1

    cv2.rectangle(image, ponto_inicial, ponto_final, cor_branca, espessura)
    cv2.rectangle(image, ponto_inicial, ponto_final, (0, 0, 0), 1)
    cv2.putText(image, str(a) + " pessoas", (5, 25), font, 0.5, (0, 0, 0), 1, cv2.LINE_AA)

    image_without_faces = cv2.bitwise_and(image, mask)
    image_without_faces = cv2.cvtColor(image_without_faces, cv2.COLOR_BGR2GRAY)
    os.makedirs(output_dir, exist_ok=True)
    output_path = os.path.join(output_dir, os.path.basename(image_path))
    cv2.imwrite(output_path, image_without_faces)
    print(f"Imagem salva em: {output_path}")

# Processa todas as imagens do diretorio input    
def process_images(input_dir, output_dir):
    global a
    a = 0
    if not os.path.exists(input_dir):
        print(f"A pasta de entrada {input_dir} não existe.")
        return

    for filename in os.listdir(input_dir):
        if filename.endswith(('.png', '.jpg', '.jpeg')):
            image_path = os.path.join(input_dir, filename)
            remove_faces_from_image(image_path, output_dir)

input_dir = "/imagem/input"
output_dir = "/imagem/pasteur1"
process_images(input_dir, output_dir)

# Criptografia de imagem e geração de key
def generate_key():
    return Fernet.generate_key()

def encrypt_image(file_path, key):
    with open(file_path, 'rb') as file:
        image_data = file.read()
    fernet = Fernet(key)
    encrypted_data = fernet.encrypt(image_data)
    return encrypted_data

def save_encrypted_image(encrypted_data, dest_path):
    with open(dest_path, 'wb') as file:
        file.write(encrypted_data)

def save_key(key, key_path):
    with open(key_path, 'wb') as file:
        file.write(key)

def process_images_encryption():
    global encrypted_file_path
    for filename in os.listdir(source_dir):
        if filename.endswith(('.png', '.jpg', '.jpeg', '.bmp', '.gif')):
            file_path = os.path.join(source_dir, filename)
            encrypted_file_path = os.path.join(dest_dir, filename + ".enc")
            key_file_path = os.path.join(key_dir, filename + ".key")

            key = generate_key()
            encrypted_data = encrypt_image(file_path, key)

            save_encrypted_image(encrypted_data, encrypted_file_path)
            save_key(key, key_file_path)

            print(f"Imagem '{filename}' criptografada com sucesso!")

if __name__ == "__main__":
    process_images_encryption()

global caminho_arquivo_destino 

# Transfere os arquivos input para a lixeira
for arquivo in os.listdir(origem):
    caminho_arquivo_origem = os.path.join(origem, arquivo)
    caminho_arquivo_destino = os.path.join(destino, arquivo)
    
    if os.path.isfile(caminho_arquivo_origem):
        shutil.move(caminho_arquivo_origem, caminho_arquivo_destino)

print("Todos os arquivos foram movidos com sucesso!")

# Fim da contagem do tempo
end_time = time.time()
execution_time = end_time - start_time
# After processing images, create a JSON output
output_data = {"quantidade_pessoas": a, "caminho_arquivo_anonimizado":output_path, "caminho_arquivo_original":caminho_arquivo_destino, "nome_arquivo":novo_nome_arquivo}

# Save to output.json
with open('output.json', 'w') as f:
    json.dump(output_data, f)
    
# Formatted Output
print(f"Tempo de execução: {execution_time:.4f} segundos", flush=True)
print(f"Total de rostos detectados: {a}", flush=True)
