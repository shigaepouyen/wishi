<script>
    // Envoie le lien « Mes cadeaux » a soi-meme : feuille de partage du telephone (SMS, mail, WhatsApp),
    // sinon copie dans le presse-papiers.
    window.wishiShareLink = async function (url) {
        if (!url) return;
        if (navigator.share) {
            try {
                await navigator.share({ title: "Mes cadeaux Wishi", text: "Mon lien pour retrouver les cadeaux que je dois offrir :", url });
                return;
            } catch (e) {
                if (e && e.name === "AbortError") return;
            }
        }
        try {
            await navigator.clipboard.writeText(url);
            alert("Lien copié ! Collez-le dans un message ou un email pour vous l'envoyer.");
        } catch (e) {
            prompt("Copiez ce lien et envoyez-le-vous :", url);
        }
    };
</script>
