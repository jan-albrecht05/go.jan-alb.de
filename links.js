// Filter links based on the selected criteria
function loadLinks(){
    console.log('loadLinks() aufgerufen');
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
        console.log('PHP geladen:', data);

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
                <div class="actions">Aktionen</div>
            </div>

            `;

        });
    });
    
}