<div class="assistente-virtual">
    <div class="botao-assistente" onclick="toggleAssistente()">
        <i class="fas fa-robot"></i>
    </div>
    
    <div class="painel-chat" style="display:none;">
        <div class="cabecalho-chat">
            <h5>Assistente Virtual</h5>
            <button class="btn-fechar" onclick="toggleAssistente()">×</button>
        </div>
        
        <div class="mensagens-chat">
            <!-- As mensagens aparecerão aqui -->
        </div>
        
        <div class="controles-chat">
            <input type="text" class="campo-mensagem" placeholder="Digite sua mensagem...">
            <button class="btn-enviar" onclick="enviarMensagem()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<style>
.assistente-virtual {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

.botao-assistente {
    width: 60px;
    height: 60px;
    background: #007bff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.botao-assistente i {
    color: white;
    font-size: 24px;
}

.painel-chat {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 300px;
    height: 400px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.cabecalho-chat {
    padding: 15px;
    background: #007bff;
    color: white;
    border-radius: 10px 10px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn-fechar {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
}

.mensagens-chat {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
}

.controles-chat {
    padding: 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
}

.campo-mensagem {
    flex: 1;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 20px;
    outline: none;
}

.btn-enviar {
    background: #007bff;
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
}

.mensagem {
    margin-bottom: 10px;
    padding: 8px 12px;
    border-radius: 15px;
    max-width: 80%;
}

.mensagem.usuario {
    background: #e9ecef;
    margin-left: auto;
}

.mensagem.assistente {
    background: #007bff;
    color: white;
    margin-right: auto;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função para mostrar/esconder o chat
    window.toggleAssistente = function() {
        const painel = document.querySelector('.painel-chat');
        painel.style.display = painel.style.display === 'none' ? 'flex' : 'none';
    };

    // Referências aos elementos do chat
    const campoMensagem = document.querySelector('.campo-mensagem');
    const botaoEnviar = document.querySelector('.btn-enviar');
    const painelMensagens = document.querySelector('.mensagens-chat');
    
    // Função para adicionar mensagem ao chat
    function adicionarMensagem(texto, tipo) {
        const mensagem = document.createElement('div');
        mensagem.className = `mensagem ${tipo}`;
        mensagem.textContent = texto;
        painelMensagens.appendChild(mensagem);
        painelMensagens.scrollTop = painelMensagens.scrollHeight;
    }
    
    // Função para enviar mensagem
    async function enviarMensagem() {
        const texto = campoMensagem.value.trim();
        if (!texto) return;
        
        // Adiciona mensagem do usuário
        adicionarMensagem(texto, 'usuario');
        
        try {
            // Envia para o backend
            const resposta = await fetch((typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/app/ia/responder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ mensagem: texto })
            });
            
            const dados = await resposta.json();
            
            // Adiciona resposta do assistente
            if (dados.texto) {
                adicionarMensagem(dados.texto, 'assistente');
                
                // Se tiver áudio habilitado, reproduz a resposta
                if (dados.audio && window.speechSynthesis) {
                    const fala = new SpeechSynthesisUtterance(dados.texto);
                    fala.lang = 'pt-PT'; // Português de Portugal
                    window.speechSynthesis.speak(fala);
                }
            }
        } catch (erro) {
            console.error('Erro ao enviar mensagem:', erro);
            adicionarMensagem('Desculpe, ocorreu um erro ao processar sua mensagem.', 'assistente');
        }
        
        // Limpa o campo de mensagem
        campoMensagem.value = '';
    }
    
    // Configura eventos
    if (botaoEnviar) {
        botaoEnviar.addEventListener('click', enviarMensagem);
    }
    
    if (campoMensagem) {
        campoMensagem.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                enviarMensagem();
            }
        });
    }
    
    // Mostra mensagem inicial
    setTimeout(() => {
        adicionarMensagem('Olá! Como posso ajudar?', 'assistente');
    }, 1000);
});
</script>