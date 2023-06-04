<style>
    body {
        background-color: <?php echo $_SESSION['theme'] === 'dark' ? '#212529' : '#fff'; ?>;
        color: <?php echo $_SESSION['theme'] === 'dark' ? '#fff' : '#212529'; ?>;
    }
    tr:nth-child(even) {
        background-color: <?php echo $_SESSION['theme'] === 'dark' ? '#212529' : '#eee'; ?>;
    }
    .modal-content {
        background-color: <?php echo $_SESSION['theme'] === 'dark' ? '#212529' : '#eee'; ?>;
    }
</style>

<button class="btn btn-dark ml-3" onclick="toggleTheme()">Toggle Dark Theme</button>

<script>
    function toggleTheme() {
        var theme = "<?php echo $_SESSION['theme']; ?>";
        var newTheme = theme === 'light' ? 'dark' : 'light';
        window.location.href = window.location.pathname + '?theme=' + newTheme;
    }
</script>
