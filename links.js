// Filter links based on the selected criteria
function loadLinks(){
    let params = new URLSearchParams({
        status: document.querySelector("#status-select").value,
        type: document.querySelector("#type-select").value,
        search:  document.querySelector("#search-input").value,
        sort: document.querySelector("#sort-select").value
    });

    fetch("php/links.php?" + params)
    .then(response => response.json())
    .then(data => {
        const listbody = document.querySelector("#link-list-content");
        listbody.innerHTML="";
        data.forEach(link=>{
            listbody.innerHTML += `
            <div class="link-item">
                <div class="short-url"><a href="${link.target}" target="_blank">${link.hash}</a></div>
                <div class="original-url">${link.target}</div>
                <div class="type ${link.type}">${link.type}</div>
                <div class="clicks">${link.clicks}${link.max_clicks !== null ? `<span class="lightgrey2">/${link.max_clicks}</span>` : ''}</div>
                <div class="status row "><span class="status-tag center ${link.active ? 'active' : 'inactive'}">${link.active ? "Aktiv" : "Inaktiv"}${link.password_hash ? '<span class="material-symbols-outlined">lock</span>' : ''}</span></div>
                <div class="created-at">${link.created_at}</div>
                <div class="ip">${link.created_by}</div>
                <div class="actions">
                    ${link.active ? `<button class="btn-secondary center material-symbols-outlined" onclick="changeVisibility(${link.id})">visibility_off</button>` : ''}
                </div>
            </div>
            `;
        });
    });
}

function changeVisibility(linkId){
    //use php/links.php to change the visibility of the link with the given id
    fetch("php/links.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            action: "changeVisibility",
            id: linkId
        })
    })
    .then(response => response.json())
    .then(data => {
    if (data.success) {
        loadLinks();
    } else {
        alert(data.message ?? "Unbekannter Fehler");
    }
    })
    .catch(err => console.error(err));
}