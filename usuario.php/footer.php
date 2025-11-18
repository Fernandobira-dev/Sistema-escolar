<footer class="text-white text-center py-0 mt-3" style="background: #0d6efd ;">
    <p>&copy; <span id="anoAtual"></span> 30 de Setembro | Todos os direitos reservados.</p>
    <div>
        <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
        <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
        <a href="#" class="text-white"><i class="bi bi-twitter"></i></a>
    </div>
</footer>

<script>
    function atualizarAnoRodape() {
        document.getElementById('anoAtual').textContent = new Date().getFullYear();
    }
    atualizarAnoRodape();
</script>
