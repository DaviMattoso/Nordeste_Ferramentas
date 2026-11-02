/**
 * Prepara a mensagem do formulário público de contato no WhatsApp.
 * Não persiste dados no servidor; o envio efetivo ocorre no aplicativo do visitante.
 */
document.addEventListener("DOMContentLoaded", () => {
    const contactForm = document.querySelector("#blog-contact-form");
    const formStatus = document.querySelector("#blog-contact-status");

    if (!contactForm || !formStatus) {
        return;
    }

    contactForm.addEventListener("submit", (event) => {
        event.preventDefault();

        if (!contactForm.reportValidity()) {
            return;
        }

        const formData = new FormData(contactForm);
        const message = [
            `Olá! Meu nome é ${formData.get("nome")}.`,
            `E-mail: ${formData.get("email")}`,
            `Assunto: ${formData.get("assunto") || "Não informado"}`,
            "",
            "Mensagem:",
            formData.get("mensagem"),
        ].join("\n");
        const whatsappUrl = `https://wa.me/${contactForm.dataset.whatsapp}?text=${encodeURIComponent(message)}`;
        const whatsappWindow = window.open(whatsappUrl, "_blank", "noopener,noreferrer");

        if (whatsappWindow) {
            whatsappWindow.opener = null;
        } else {
            window.location.href = whatsappUrl;
        }

        formStatus.textContent = "Mensagem preparada no WhatsApp.";
        formStatus.dataset.state = "success";
    });
});
