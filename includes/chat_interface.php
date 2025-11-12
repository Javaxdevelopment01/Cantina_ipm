<?php
function renderChatInterface() {
    ?>
    <div class="chat-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
        <div class="chat-toggle btn btn-primary rounded-circle" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="fas fa-robot"></i>
        </div>
        
        <div class="chat-container" style="display: none; position: absolute; bottom: 70px; right: 0; width: 300px; height: 400px; background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow: hidden;">
            <div class="chat-header bg-primary text-white p-3">
                <h5 class="m-0">Assistente Virtual</h5>
            </div>
            
            <div class="chat-box p-3" style="height: 300px; overflow-y: auto;">
                <!-- Mensagens aparecerão aqui -->
            </div>
            
            <div class="chat-input p-3 border-top">
                <div class="input-group">
                    <button class="btn btn-success btn-audio me-2" title="Clique e segure para falar">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <input type="text" class="form-control chat-message" placeholder="Digite sua mensagem...">
                    <button class="btn btn-primary send-message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="audio-status" style="display: none;">
                    <div class="audio-wave"></div>
                    <span>Ouvindo...</span>
                </div>
            </div>
        </div>
    </div>

    <style>
        .message {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 15px;
            max-width: 80%;
        }
        
        .message.user {
            background: #e9ecef;
            margin-left: auto;
        }
        
        .message.assistant {
            background: #007bff;
            color: white;
            margin-right: auto;
        }
        
        .chat-container {
            transition: all 0.3s ease;
        }

        .btn-audio {
            width: 40px;
            height: 38px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-audio.recording {
            background-color: #dc3545 !important;
            animation: pulse 1.5s infinite;
        }

        .audio-status {
            position: absolute;
            bottom: 70px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .audio-wave {
            width: 10px;
            height: 10px;
            background: #dc3545;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chatToggle = document.querySelector('.chat-toggle');
            const chatContainer = document.querySelector('.chat-container');
            const chatInput = document.querySelector('.chat-message');
            const sendButton = document.querySelector('.send-message');
            const audioButton = document.querySelector('.btn-audio');
            const audioStatus = document.querySelector('.audio-status');
            const chatBox = document.querySelector('.chat-box');

            // Configuração do reconhecimento de voz (compatível com diferentes navegadores)
            let recognition;
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition || null;
            if (SpeechRecognition) {
                recognition = new SpeechRecognition();
                recognition.continuous = false;
                recognition.interimResults = false;
                recognition.lang = 'pt-PT'; // Português
            } else {
                // Se não houver suporte, apenas oculta o botão de áudio (se existir)
                if (audioButton) audioButton.style.display = 'none';
            }

            // Toggle chat
            chatToggle.addEventListener('click', () => {
                chatContainer.style.display = chatContainer.style.display === 'none' ? 'block' : 'none';
                if (chatContainer.style.display === 'block') {
                    chatInput.focus();
                }
            });

            // Enviar mensagem
            function sendMessage() {
                const message = chatInput.value.trim();
                if (message) {
                    // Adiciona mensagem do usuário
                    const msgDiv = document.createElement('div');
                    msgDiv.className = 'message user';
                    msgDiv.textContent = message;
                    chatBox.appendChild(msgDiv);
                    
                    // Limpa input e rola para baixo
                    chatInput.value = '';
                    chatBox.scrollTop = chatBox.scrollHeight;
                    
                    // Envia para o assistente
                    if (window.AI) {
                        window.AI.sendMessage(message);
                    }
                }
            }

            // Configuração do botão de áudio
            if (audioButton && recognition) {
                audioButton.addEventListener('mousedown', () => {
                    audioButton.classList.add('recording');
                    audioStatus.style.display = 'flex';
                    recognition.start();
                });

                audioButton.addEventListener('mouseup', () => {
                    audioButton.classList.remove('recording');
                    audioStatus.style.display = 'none';
                    recognition.stop();
                });

                audioButton.addEventListener('mouseleave', () => {
                    if (audioButton.classList.contains('recording')) {
                        audioButton.classList.remove('recording');
                        audioStatus.style.display = 'none';
                        recognition.stop();
                    }
                });

                recognition.onresult = (event) => {
                    const text = event.results[0][0].transcript;
                    chatInput.value = text;
                    sendMessage();
                };

                recognition.onerror = () => {
                    audioButton.classList.remove('recording');
                    audioStatus.style.display = 'none';
                };
            }

            sendButton.addEventListener('click', sendMessage);
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });

            // Mensagem inicial do assistente
            setTimeout(() => {
                const msgDiv = document.createElement('div');
                msgDiv.className = 'message assistant';
                msgDiv.textContent = 'Olá! Como posso ajudar? Você pode digitar ou usar o botão do microfone para falar comigo!';
                chatBox.appendChild(msgDiv);
            }, 1000);
        });
    </script>
    <?php
}