</div>
<footer class="app-footer">
  <?= e(APP_NAME) ?> &middot; &copy; <?= date('Y') ?>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Lightbox for viewing photos full-size
document.addEventListener('click', function (e) {
  const img = e.target.closest('.photo-thumb');
  if (!img) return;
  const modalImg = document.getElementById('lightboxImg');
  if (!modalImg) return;
  modalImg.src = img.src;
  const modal = new bootstrap.Modal(document.getElementById('lightboxModal'));
  modal.show();
});
</script>
<div class="modal fade" id="lightboxModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark">
      <div class="modal-body text-center p-0">
        <img id="lightboxImg" src="" class="img-fluid rounded" style="max-height:85vh;">
      </div>
    </div>
  </div>
</div>
</body>
</html>
