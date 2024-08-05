// this goes to /var/www/html/[template name]/index.php

<script>
document.addEventListener('DOMContentLoaded', function () {
    function addConfirmButtonListeners() {
        document.querySelectorAll('.confirm-button').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const email = this.getAttribute('data-email');
                const firstName = this.getAttribute('data-first_name');
                const lastName = this.getAttribute('data-last_name');
                const table = this.closest('[data-tbl]').getAttribute('data-tbl');
                const quota = this.closest('[data-quota]').getAttribute('data-quota');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://iaf.kemlu.go.id/index.php?option=com_ajax&plugin=confirmbutton&task=confirmItem&format=json');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        alert('Confirmation successful');
                        button.parentElement.innerHTML = 'Confirmed <button class="rescind-button" data-id="' + id + '" data-tbl="' + table + '">Rescind Confirmation</button>';
                        addRescindButtonListeners();
                    } else {
                        console.log(xhr.responseText);  // Log the response
                        alert('Error during confirmation');
                    }
                };
                xhr.send('id=' + id + '&email=' + email + '&first_name=' + firstName + '&last_name=' + lastName + '&tbl=' + table + '&quota=' + quota);
            });
        });
    }

    function addRescindButtonListeners() {
        document.querySelectorAll('.rescind-button').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const table = this.closest('[data-tbl]').getAttribute('data-tbl');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://iaf.kemlu.go.id/index.php?option=com_ajax&plugin=confirmbutton&task=rescindItem&format=json');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        alert('Rescind successful');
                        button.parentElement.innerHTML = '<button class="confirm-button" data-id="' + id + '" data-tbl="' + table + '">Confirm</button>';
                        addConfirmButtonListeners();
                    } else {
                        console.log(xhr.responseText);  // Log the response
                        alert('Error during rescind');
                    }
                };
                xhr.send('id=' + id + '&tbl=' + table);
            });
        });
    }

    function addRejectButtonListeners() {
        document.querySelectorAll('.reject-button').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const email = this.getAttribute('data-email');
                const firstName = this.getAttribute('data-first_name');
                const lastName = this.getAttribute('data-last_name');
                const table = this.closest('[data-tbl]').getAttribute('data-tbl');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://iaf.kemlu.go.id/index.php?option=com_ajax&plugin=confirmbutton&task=rejectItem&format=json');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        alert('Rejection successful');
                        button.parentElement.innerHTML = 'Rejected <button class="rescind-reject-button" data-id="' + id + '" data-tbl="' + table + '">Rescind Rejection</button>';
                        addRescindRejectButtonListeners();
                    } else {
                        console.log(xhr.responseText);  // Log the response
                        alert('Error during rejection');
                    }
                };
                xhr.send('id=' + id + '&email=' + email + '&first_name=' + firstName + '&last_name=' + lastName + '&tbl=' + table);
            });
        });
    }

    function addRescindRejectButtonListeners() {
        document.querySelectorAll('.rescind-reject-button').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const table = this.closest('[data-tbl]').getAttribute('data-tbl');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'https://iaf.kemlu.go.id/index.php?option=com_ajax&plugin=confirmbutton&task=rescindReject&format=json');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        alert('Rescind rejection successful');
                        button.parentElement.innerHTML = '<button class="confirm-button" data-id="' + id + '" data-tbl="' + table + '">Confirm</button>';
                        addConfirmButtonListeners();
                    } else {
                        console.log(xhr.responseText);  // Log the response
                        alert('Error during rescind rejection');
                    }
                };
                xhr.send('id=' + id + '&tbl=' + table);
            });
        });
    }

    // Initial load
    addConfirmButtonListeners();
    addRescindButtonListeners();
    addRejectButtonListeners();
    addRescindRejectButtonListeners();
});
</script>
