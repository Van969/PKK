document.addEventListener('DOMContentLoaded', function () {

  // ==== 🔍 PENCARIAN NAMA ====
  const inputCari = document.getElementById('cariNama');
  const table = document.getElementById('tbodyPembayaran');
  if (inputCari && table) {
    inputCari.addEventListener('keyup', () => {
      const filter = inputCari.value.toLowerCase();
      table.querySelectorAll('tr').forEach(tr => {
        const nama = tr.cells[1]?.textContent.toLowerCase() || '';
        tr.style.display = nama.includes(filter) ? '' : 'none';
      });
    });
  }

  // ==== MODAL TAMBAH SISWA ====
  const btnTambah = document.getElementById('btnTambah');
  const modalTambahEl = document.getElementById('modalTambah');
  const formTambah = document.getElementById('formTambah');
  if (btnTambah && modalTambahEl && formTambah) {
    const modalTambah = new bootstrap.Modal(modalTambahEl);
    btnTambah.addEventListener('click', () => {
      formTambah.reset();
      modalTambah.show();
    });
    formTambah.addEventListener('submit', (e) => {
      e.preventDefault();
      fetch('./add_siswa.php', {
        method: 'POST',
        body: new FormData(formTambah)
      })
      .then(res => res.text())
      .then(result => {
        if (result.includes('ok')) location.reload();
        else alert('❌ Gagal menambah siswa: ' + result);
      });
    });
  }

  // ==== MODAL EDIT SISWA ====
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      fetch('./get_siswa.php?id=' + id)
        .then(res => res.json())
        .then(data => {
          document.getElementById('edit_id').value = data.id;
          document.getElementById('edit_nama').value = data.nama;
          new bootstrap.Modal(document.getElementById('modalEdit')).show();
        });
    });
  });

  const formEdit = document.getElementById('formEdit');
  if (formEdit) {
    formEdit.addEventListener('submit', (e) => {
      e.preventDefault();
      fetch('./update_siswa.php', {
        method: 'POST',
        body: new FormData(formEdit)
      })
      .then(res => res.text())
      .then(result => {
        if (result.includes('ok')) location.reload();
        else alert("❌ Gagal update: " + result);
      });
    });
  }

  // ==== HAPUS SISWA ====
  document.querySelectorAll('.btn-hapus').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      if (!confirm('Hapus siswa & semua datanya?')) return;
      const fd = new FormData();
      fd.append('id', id);
      fetch('delete_siswa.php', {
        method: 'POST',
        body: fd
      })
      .then(res => res.text())
      .then(text => {
        if (text.includes('ok')) location.reload();
        else alert('❌ Gagal hapus: ' + text);
      });
    });
  });

  // ==== ✅ CHECKBOX PEMBAYARAN MINGGUAN ====
  document.addEventListener('change', async (e) => {
    if (!e.target.classList.contains('bayar-checkbox')) return;

    const cb = e.target;
    const id = cb.dataset.siswa;
    const minggu = cb.dataset.minggu;
    const bulan = cb.dataset.bulan;
    const tahun = cb.dataset.tahun;
    const status = cb.checked ? 1 : 0;
    const bayarPerMinggu = 5000;

    console.log(">>> SEND:", { id, minggu, bulan, tahun, status });

    if (!bulan || !tahun) {
      alert("❌ Bulan / Tahun tidak ditemukan!");
      cb.checked = !status; // rollback
      return;
    }

    const row = cb.closest('tr');
    const semuaMinggu = row.querySelectorAll('.bayar-checkbox');
    const belumBayar = [...semuaMinggu].filter(x => !x.checked).length;
    const totalCell = row.querySelector('td:last-child');
    totalCell.innerHTML = belumBayar === 0
      ? `<span class="text-success fw-bold">Lunas</span>`
      : `<span class="text-danger">Rp ${(belumBayar * bayarPerMinggu).toLocaleString('id-ID')}</span>`;

    try {
      // Panggil file update BARU
      const resp = await fetch('update_pembayaran_baru.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id, minggu, status, bulan, tahun })
      });
      const text = await resp.text();
      console.log("SERVER:", text);

      if (!text.toLowerCase().includes('ok')) {
        alert('❌ Gagal update!');
        cb.checked = !status; // rollback UI
      } else {
        console.log("✅ Data tersimpan. Refresh sidebar...");
        updateSidebar(); // update total langsung
      }

    } catch (err) {
      alert("Koneksi error!");
      cb.checked = !status;
    }
  });

}); // DOMContentLoaded END

// ==== 🔄 REFRESH SIDEBAR ====
function updateSidebar() {
  fetch('get_sidebar.php')
    .then(res => res.json())
    .then(data => {
      if (!data) return;

      const formatRupiah = num => 'Rp ' + new Intl.NumberFormat('id-ID').format(num);

      const totalEl = document.getElementById("totalPemasukanSidebar");
      const pengeluaranEl = document.getElementById("totalPengeluaranSidebar");
      const saldoEl = document.getElementById("saldoBersihSidebar");

      if (totalEl) totalEl.textContent = formatRupiah(data.total_pemasukan);
      if (pengeluaranEl) pengeluaranEl.textContent = formatRupiah(data.total_pengeluaran);
      if (saldoEl) {
        saldoEl.textContent = formatRupiah(data.saldo_bersih);
        saldoEl.className = 'h5 mt-2 ' + (data.saldo_bersih < 0 ? 'text-danger' : 'text-success');
      }
    })
    .catch(err => console.error('❌ Gagal refresh sidebar:', err));
}
