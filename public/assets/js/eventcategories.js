
console.log('Script loaded');
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    const deleteButtons = document.querySelectorAll('.delete-category');
    console.log('Found delete buttons:', deleteButtons.length);
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Delete button clicked');
            const categoryId = this.getAttribute('data-id');
            console.log('Category ID:', categoryId);
            
            Swal.fire({
                title: 'Delete "' + this.closest('tr').querySelector('td:nth-child(2)').textContent.trim() + '"',
                text: "Are you sure you want to delete this category?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                console.log('SweetAlert result:', result);
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-form-' + categoryId);
                    console.log('Submitting form:', form);
                    if (form) {
                        form.submit();
                    } else {
                        console.error('Form not found for ID:', 'delete-form-' + categoryId);
                    }
                }
            });
        });
    });
});
