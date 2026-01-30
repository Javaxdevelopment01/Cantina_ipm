// Função para inicializar o assistente AI
function initAI() {
  const synth = window.speechSynthesis;
  let speaking = false;

  // Configura as vozes em português
  let voices = [];
  function setVoices() {
    voices = synth.getVoices().filter((voice) => voice.lang.includes("pt"));
  }

  setVoices();
  if (speechSynthesis.onvoiceschanged !== undefined) {
    speechSynthesis.onvoiceschanged = setVoices;
  }

  // Função para falar o texto
  function speak(text) {
    if (speaking) {
      synth.cancel();
    }

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.voice = voices[0];
    utterance.rate = 1;
    utterance.pitch = 1;
    utterance.volume = 1;

    speaking = true;
    utterance.onend = () => {
      speaking = false;
    };

    synth.speak(utterance);
  }

  // Função para enviar mensagem para a IA
  async function sendMessage(message) {
    try {
      const response = await fetch(
        (typeof BASE_URL !== "undefined" ? BASE_URL : "") +
          "/app/ia/responder.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ mensagem: message }),
        }
      );

      const data = await response.json();

      if (data.texto) {
        // Adiciona a resposta ao chat (se existir um elemento de chat)
        const chatBox = document.querySelector(".chat-box");
        if (chatBox) {
          const msgDiv = document.createElement("div");
          msgDiv.className = "message assistant";
          msgDiv.textContent = data.texto;
          chatBox.appendChild(msgDiv);
          chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Fala a resposta se audio estiver habilitado
        if (data.audio) {
          speak(data.texto);
        }
      }

      return data;
    } catch (error) {
      console.error("Erro ao enviar mensagem:", error);
      return { erro: "Erro ao processar mensagem" };
    }
  }

  // Verifica produtos com estoque baixo periodicamente
  async function checkLowStock() {
    const response = await fetch(
      (typeof BASE_URL !== "undefined" ? BASE_URL : "") +
        "/app/ia/responder.php",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ mensagem: "verificar estoque" }),
      }
    );

    const data = await response.json();
    if (data.texto && !speaking) {
      speak(data.texto);
    }
  }

  // Verifica estoque a cada 5 minutos
  setInterval(checkLowStock, 5 * 60 * 1000);

  // Executa verificação inicial
  checkLowStock();

  return {
    sendMessage,
    speak,
  };
}

// Inicializa o assistente quando o documento estiver pronto
document.addEventListener("DOMContentLoaded", () => {
  window.AI = initAI();
});
