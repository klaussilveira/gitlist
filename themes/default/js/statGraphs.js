/* GitList repository statistic charts */
var colors = ['#3498DB', '#2ECC71', '#9B59B6', '#E74C3C', '#1ABC9C', '#F39C12', '#95A5A6'];

/* Commits by Date */
if (typeof commitsByDate !== 'undefined') {
    $("#commitsByDate").highcharts({
        colors: colors,
        chart: {
            type: 'areaspline',
            zoomType: 'x',
            spacing: 50
        },
        plotOptions: {
            series: {
                lineWidth: 1,
                marker: {
                    enabled: false
                }
            }
        },
        title: {
            text: ''
        },
        legend: {
            enabled: false
        },
        xAxis: {
            categories: commitsByDate.x,
            tickInterval: parseInt(commitsByDate.x.length / 20),
            labels: {
                rotation: -45,
                y: 35
            }
        },
        yAxis: {
            title: {
                text: 'Commits'
            }
        },
        series: [{
            name: 'Commits',
            data: commitsByDate.y
        }]
    });
}

/* Commits by Hour */
if (typeof commitsByHour !== 'undefined') {
    $("#commitsByHour").highcharts({
        colors: colors,
        chart: {
            type: 'bar'
        },
        title: {
            text: ''
        },
        legend: {
            enabled: false
        },
        xAxis: {
            categories: commitsByHour.x,
        },
        yAxis: {
            title: {
                text: 'Commits'
            }
        },
        series: [{
            name: 'Commits',
            data: commitsByHour.y
        }]
    });
}

/* Commits by Day */
if (typeof commitsByDay !== 'undefined') {
    $("#commitsByDay").highcharts({
        colors: colors,
        chart: {
            type: 'pie'
        },
        title: {
            text: ''
        },
        series: [{
            name: 'Commits',
            data: commitsByDay
        }]
    });
}

/* Commits by contributor */
if (typeof contributors !== 'undefined') {
    for (var i = contributors.length - 1; i >= 0; i--) {
        var title = contributors[i].name + ' | ' + contributors[i].commits + ((contributors[i].commits > 1) ? ' commits' : ' commit');
        $("#contributorChart-" + i).highcharts({
            colors: colors,
            chart: {
                type: 'areaspline',
                zoomType: 'x',
                spacing: 20,
                borderColor: '#ddd',
                borderWidth: 1
            },
            plotOptions: {
                series: {
                    lineWidth: 1,
                    marker: {
                        enabled: false
                    }
                }
            },
            title: {
                text: title,
                align: 'left',
                style: {
                    fontWeight: 700,
                    fontSize: '24px',
                    color: '#333'
                }
            },
            subtitle: {
                text: contributors[i].email,
                align: 'left',
                style: {
                    color: '#666',
                    fontSize: '16px'
                }
            },
            legend: {
                enabled: true,
                margin: 45
            },
            xAxis: {
                categories: contributors[i].x,
                tickInterval: parseInt(contributors[i].x.length / 20),
                labels: {
                    rotation: -45,
                    y: 35
                }
            },
            yAxis: {
                title: {
                    text: ''
                }
            },
            series: [{
                name: 'Commits',
                data: contributors[i].y
            }]
        });
    }
}
