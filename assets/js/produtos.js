document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("formProduto");
  const modal = document.getElementById("modalProduto");

  document.getElementById("btnAdicionar").addEventListener("click", () => {
    modal.style.display = "block";
    form.reset();
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = new FormData(form);
    const acao = data.get("id") ? "atualizar" : "criar";
    data.append("acao", acao);

    const res = await fetch("../app/Controllers/ProdutoController.php", {
      method: "POST",
      body: data,
    });
    const json = await res.json();
    if (json) location.reload();
  });

  document.querySelectorAll(".btnDeletar").forEach((btn) => {
    btn.addEventListener("click", async () => {
      if (confirm("Deseja realmente deletar?")) {
        const id = btn.getAttribute("data-id");
        const formData = new FormData();
        formData.append("acao", "deletar");
        formData.append("id", id);
        const res = await fetch("../app/Controllers/ProdutoController.php", {
          method: "POST",
          body: formData,
        });
        const json = await res.json();
        if (json) location.reload();
      }
    });
  });
});
