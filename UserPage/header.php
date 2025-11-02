<?php
// header.php
if(!isset($_SESSION)) session_start();
?>
<nav class="navbar navbar-light bg-white shadow-sm rounded mb-4 px-3 d-flex justify-content-between align-items-center">
  <div class="d-flex align-items-center">
    <a href="index.php" class="btn btn-light border-0 me-2">
      
    </a>
    <h4 class="mb-0">Pembayaran Kas Kelas</h4>
  </div>
  
  <div class="d-flex align-items-center">
    <span class="me-3"><i class="fa fa-user"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></span>
    <div class="dropdown">
      <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <i class="fa fa-gear"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item text-danger" href="../LoginPage/HalamanLogin.html">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
