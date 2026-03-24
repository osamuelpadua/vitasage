(function () {
  const forms = document.querySelectorAll("[data-contact-form]");

  if (!forms.length) {
    return;
  }

  const defaultErrorMessage =
    "Nao foi possivel enviar a mensagem. Tente novamente.";
  const sendingLabel = "Sending...";

  function getFieldValue(formData, name) {
    const value = formData.get(name);
    return typeof value === "string" ? value.trim() : "";
  }

  function setStatus(element, message, type) {
    if (!element) {
      return;
    }

    element.hidden = false;
    element.textContent = message;
    element.classList.remove("is-success", "is-error");
    element.classList.add(type === "success" ? "is-success" : "is-error");
  }

  function clearStatus(element) {
    if (!element) {
      return;
    }

    element.hidden = true;
    element.textContent = "";
    element.classList.remove("is-success", "is-error");
  }

  function validateForm(formData) {
    const fullName = getFieldValue(formData, "full-name");
    const email = getFieldValue(formData, "email");
    const message = getFieldValue(formData, "message");
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!fullName) {
      return "Preencha o campo Full name.";
    }

    if (!email) {
      return "Preencha o campo E-mail.";
    }

    if (!emailPattern.test(email)) {
      return "Informe um e-mail valido.";
    }

    if (!message) {
      return "Preencha o campo Message.";
    }

    return "";
  }

  async function handleSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const submitButton = form.querySelector("[data-submit-button]");
    const statusElement = form.querySelector("[data-form-status]");
    const originalLabel = submitButton ? submitButton.textContent.trim() : "";
    const formData = new FormData(form);
    const validationError = validateForm(formData);

    clearStatus(statusElement);

    if (validationError) {
      setStatus(statusElement, validationError, "error");
      return;
    }

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = sendingLabel;
    }

    try {
      const response = await fetch(form.getAttribute("action") || "send.php", {
        method: "POST",
        body: formData,
        headers: {
          Accept: "application/json",
        },
      });

      let payload = null;

      try {
        payload = await response.json();
      } catch (parseError) {
        payload = null;
      }

      if (!response.ok || !payload || payload.success !== true) {
        const message =
          payload && typeof payload.message === "string"
            ? payload.message
            : defaultErrorMessage;

        setStatus(statusElement, message, "error");
        return;
      }

      setStatus(statusElement, payload.message, "success");
      form.reset();
    } catch (error) {
      setStatus(statusElement, defaultErrorMessage, "error");
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = originalLabel;
      }
    }
  }

  forms.forEach((form) => {
    form.addEventListener("submit", handleSubmit);
  });
})();
