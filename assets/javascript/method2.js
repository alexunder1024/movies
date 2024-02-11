document.addEventListener('DOMContentLoaded', function() {

    let urlArr = window.location.href.split("/");
    let id = (urlArr[urlArr.length - 1])
    let addCommentBtn = document.getElementById('add-comment-button');
    if (addCommentBtn) addCommentBtn.addEventListener('click', async function(event) {
        event.preventDefault();
        await submitCommentForm(event);
    });

    getComments(id);

    async function getComments(id) {
        try {
            let response = await fetch(`/show-comments/${id}`)
            if (!response.ok) {
                throw new Error('Error loading content')
            }
            let data = await response.text()
            let container = document.querySelector('.comments-section');
            container.innerHTML = data

            let editCommentBtn = document.getElementsByClassName('edit-comment-btn');
            let deleteCommentBtn = document.getElementsByClassName('delete-comment-btn');
            for (const btn of editCommentBtn) {
                btn.addEventListener('click', editComment)
            }
            for (const btn of deleteCommentBtn) {
                btn.addEventListener('click', deleteComment)
            }


        } catch (error) {
            console.log(error)
        }
    }

    async function editComment(e) {
        let commentId = this.closest('.mb-4').getAttribute('id')

        try {
            let response = await fetch(`/movies/${id}/comment/${commentId}/edit`);
            if (!response.ok) {
                throw new Error('Error loading content');
            }
            let data = await response.text();

            let commentContainer = document.createElement("div");
            commentContainer.classList.add('comment-container');
            commentContainer.innerHTML = data;

            let elem = this.closest('.mb-2');

            let commentText = elem.nextElementSibling;
            commentText.style.display = 'none';
            elem.after(commentContainer)
            let submitButton = elem.nextSibling.querySelector('button');


            e.target.style.display = 'none';
            let closeBtn = document.createElement("button");
            closeBtn.innerText = "Close";
            closeBtn.classList.add('close-btn');
            closeBtn.addEventListener('click', closeEditSection(elem))
            e.target.after(closeBtn)


            submitButton.addEventListener('click', async function (e) {
                e.preventDefault();

                let form = e.target.closest("form");
                let formData = new FormData(form)

                let responce1 = await fetch(`/movies/${id}/comment/${commentId}/edit`, {
                    method: 'POST',
                    body: formData
                })

                if (!responce1.ok) {
                    throw new Error('Network response was not ok')
                }

                let data1 = await responce1.json();
                elem.parentElement.querySelector('p').innerText = data1;
                closeEditSection(elem)();

            })

        } catch (error) {
            console.error(error.message);
        }
    }

    async function deleteComment(e) {
        let commentId = this.closest('.mb-4').getAttribute('id');

        try {
            const response = await fetch(`/movies/delete-comment/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`Не вдалося видалити коментар. Статус: ${response.status}`);
            }

            let data = await response.json();

            if(data[0] === 'success') this.closest('.mb-4').remove();

        } catch (error) {
            console.error('Помилка під час видалення коментаря:', error.message);
        }
    }

    function closeEditSection(elem) {
        return function () {
            elem.nextElementSibling.remove();
            elem.nextElementSibling.style.display = 'block';
            elem.querySelector('.edit-comment-btn').style.display = 'block';
            elem.querySelector('.close-btn').remove();
        }
    }

    async function submitCommentForm(e) {
        let addCommentForm = e.target.closest('form')
        let addCommentFormData = new FormData(addCommentForm)

        let resp = await fetch(`/movies/${id}/add-comment`, {
            method: 'POST',
            body: addCommentFormData
        })

        if (!resp.ok) {
            throw new Error('Network response was not ok')
        }


        let data = await resp.text()
        let container = document.querySelector('.comments-section');
        let wrapper = document.createElement('div');
        wrapper.innerHTML = data;
        container.insertBefore(wrapper.firstElementChild.nextElementSibling, container.firstChild)
        container.insertBefore(wrapper.firstElementChild, container.firstChild)

        container.querySelector('.edit-comment-btn').addEventListener('click', editComment);
        container.querySelector('.delete-comment-btn').addEventListener('click', deleteComment);

        e.target.previousElementSibling.querySelector('#comment_form_text').value = ''
    }

});
