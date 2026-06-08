export function initVoiceButton(buttonId, onResult) {
    const button = document.getElementById(buttonId);
    if (!button) return;
    
    let recognition = null;
    
    if ('webkitSpeechRecognition' in window) {
        const SpeechRecognition = window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = 'fil-PH';
        
        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            if (onResult) onResult(transcript);
            button.textContent = '🎤 Voice';
            button.disabled = false;
        };
        
        recognition.onerror = () => {
            button.textContent = '🎤 Voice';
            button.disabled = false;
        };
        
        recognition.onend = () => {
            button.textContent = '🎤 Voice';
            button.disabled = false;
        };
    } else {
        button.disabled = true;
        button.textContent = '❌ Voice not supported';
        return;
    }
    
    button.addEventListener('click', () => {
        if (recognition) {
            button.textContent = '🎤 Listening...';
            button.disabled = true;
            recognition.start();
        }
    });
}