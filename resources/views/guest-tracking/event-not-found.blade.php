<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
      .vh-100 {
        min-height: 100vh;
      }
      .icon-animate {
        animation: pulse 2s infinite;
      }
      @keyframes pulse {
        0% {
          transform: scale(1);
        }
        50% {
          transform: scale(1.1);
        }
        100% {
          transform: scale(1);
        }
      }
      .contact-link {
        text-decoration: none;
        transition: transform 0.2s;
      }
      .contact-link:hover {
        transform: translateY(-3px);
      }
    </style>
  </head>
  <body class="bg-light">
    <div class="container vh-100 d-flex align-items-center justify-content-center">
      <div class="card shadow-sm" style="max-width: 400px;">
        <div class="card-body text-center p-5">
          <i class="fas fa-circle-exclamation text-danger mb-3 icon-animate" style="font-size: 4rem;"></i>
          <h2 class="card-title mb-3">Event Not Found</h2>
          <p class="card-text text-muted mb-4">The event you want to track your invitees does not exist in our system. Please verify the event details or contact our support team for assistance.</p>

          <div class="d-flex flex-column gap-3">
            <p class="mb-2">Need assistance? Contact us:</p>
            <a href="tel:+255767718026" class="btn btn-outline-primary contact-link">
              <i class="fas fa-phone me-2"></i> Call Support
            </a>
            <a href="https://wa.me/255767718026" target="_blank" class="btn btn-outline-success contact-link">
              <i class="fab fa-whatsapp me-2"></i> WhatsApp Support
            </a>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
  </body>
</html>