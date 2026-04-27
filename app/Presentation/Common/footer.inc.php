<?php
/**
 *
 * @author: Guntmar Höche
 * @license: TBD
 */
?>
 <hr>
  <div class="container main-container">
  	<footer class="d-flex flex-column flex-md-row justify-content-between gap-2 pb-4 text-muted small">
	        <p class="mb-0">Powered by <a href="http://www.derguntmar.de" target="_blank" rel="noopener">derguntmar.de</a></p>
          <p class="mb-0">
            <a href="<?php echo htmlspecialchars(mds_route_path('privacy.php'), ENT_QUOTES, 'UTF-8'); ?>">Datenschutz</a>
            &middot;
            <a href="<?php echo htmlspecialchars(mds_route_path('imprint.php'), ENT_QUOTES, 'UTF-8'); ?>">Impressum</a>
          </p>
	      </footer>
	   </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  </body>
</html>
