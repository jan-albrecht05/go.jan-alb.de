let pieChart;
let barChart;

loadAnalytics();

document.getElementById("analytics-sort").addEventListener("change",loadAnalytics);

async function loadAnalytics(){
    const sort=document.getElementById("analytics-sort").value;
    const response=await fetch(
        "php/analytics.php?sort="+sort
    );
    const data=await response.json();
    drawCharts(data,sort);
}

function drawCharts(data,sort){
    const labels=data.map(d=>d.domain);
    const values=data.map(d=>
        sort==="links"
            ? d.links
            : d.clicks
    );
    const colors=[
        "#667eea",
        "#764ba2",
        "#f093fb",
        "#4facfe",
        "#43e97b",
        "#fa709a",
        "#30cfd0",
        "#ffb347",
        "#b721ff",
        "#00dbde"
    ];
    if(pieChart)
        pieChart.destroy();
    if(barChart)
        barChart.destroy();
    pieChart=new Chart(
        document.getElementById("domainPie"),
        {
            type:"doughnut",
            data:{
                labels,
                datasets:[{
                    data:values,
                    backgroundColor:colors
                }]
            },
            options:{
                responsive:true,
                animation:{
                    duration:1200
                },
                plugins:{
                    legend:{
                        position:"bottom"
                    }
                }
            }
        }
    );
    barChart=new Chart(
        document.getElementById("domainBar"),
        {
            type:"bar",
            data:{
                labels,
                datasets:[{
                    data:values,
                    backgroundColor:colors
                }]
            },
            options:{
                responsive:true,
                indexAxis:"y",
                animation:{
                    duration:1200
                },
                plugins:{
                    legend:{
                        display:false
                    }
                }
            }
        }
    );

}