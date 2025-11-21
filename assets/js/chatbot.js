const chatBody = document.querySelector(".chat-body");
const messageInput = document.querySelector(".message-input");
const sendMessageButton = document.querySelector("#send-message");
const fileInput = document.querySelector("#file-input");
const fileUploadwrapper = document.querySelector(".file-upload-wrapper");
const chatToggler = document.querySelector("#chatbot-toggler");


// API setup
// const API_KEY = "AIzaSyDc_wD2d9yfSdM3war3hygEp34xOcrUAnE";
// const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${API_KEY}';


const userData = {
    message: null,
    file: {
        data: null,
        mine_type: null
    }
}


// creating message element with dynamic classes and returning it
const createMessageElement = (content, ...classes) => {
    const div = document.createElement("div");
    div.classList.add("message", ...classes);
    div.innerHTML = content;
    return div;
}

    const generateBotResponse = async () => {
        const requestOptions = {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                contents: [{
                    parts: [{ text: userData.message }, ...( userData.file.data ? [{ inline_data: userData.file }] : [])]
                }]
            })
        }
        try {
            const response = await fetch(API_URL, requestOptions);
            const data = await response.json();
            if(!response.ok) throw new Error(data.error.message);

            console.log(data);

        } catch(error) {
            console.log(error);

        }

    }

// handling out going user messages
const handleOutgoingMessage = (e) => {
    e.preventDefault(); //preventing form from submitting message
    // am storing a user message by creating a global object, making it accessible throughout the project
    userData.message = messageInput.value.trim();

    // now am clearing the textarea after the message is sent
    messageInput.value = "";


    // creating and displaying user message
    const messageContent = '<div class="message-text"></div> ${userData.file.data ? `<img src="data: ${userData.file.mine_type};base64,${userData.file.data}"/>` : ""}'; //something fishy is going on here

    const outgoingMessageDiv = createMessageElement(messageContent, "user-message");
    outgoingMessageDiv.querySelector(".message-text").textContent = userData.message;
    chatBody.appendChild(outgoingMessageDiv);

    // now am going to display the loading dots when message is sent after a delay
    setTimeout(() => {
        const messageContent = '<span class="material-symbols-rounded" id="bot-avator">smart_toy</span><div class="message-text"><div class="thinking-indicator"><div class="dot"></div><div class="dot"></div><div class="dot"></div></div></div>';

        const incomingMessageDiv = createMessageElement(messageContent, "bot-message", "thinking");
        
        chatBody.appendChild(incomingMessageDiv);
        generateBotResponse();
    }, 600);
}
// Handling enter key when pressed for sending messages
messageInput.addEventListener("keydown", (e) => {
    const userMessage = e.target.value.trim();
    if(e.key === "Enter" && userMessage) {
        handleOutgoingMessage(e);
    }
});

fileInput.addEventListener("change", () => {
    const file = fileInput.files[0];
    if(!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        fileUploadwrapper.querySelector("img").src = e.target.result;
        fileUploadwrapper.classList.add("file-uploaded");
        const base64String = e.target.result.split(",")[1];


        userData.file = {
            data: base64String,
            mine_type: file.type
        }

        fileInput.value = "";
    }

    reader.readAsDataURL(file);
})

const picker = new EmojiMart.Picker({
    theme: "light",
    skinTonePosition: "none",
    previewPosition: "none",
    onEmojiSelect: (emoji) => {
        const { selectionStart: start, selectionEnd: end } = messageInput;
        messageInput.setRangeText(emoji.native, start, end, "end");
        messageInput.focus();
    },
    onClickOutside: (e) => {
        if(e.target.id === "emoji-picker") {
            document.body.classList.toggle("show-emoli-picker");
        }else {
            document.body.classList.remove("show-emoli-picker");
        }
    }
    
});

document.querySelector(".chat-form").appendChild(picker);

sendMessageButton.addEventListener("click", (e) => handleOutgoingMessage(e))    
document.querySelector("#file-upload").addEventListener("click", () => fileInput.click());
chatToggler.addEventListener("click", () => document.body.classList.toggle("show-chatbot"));