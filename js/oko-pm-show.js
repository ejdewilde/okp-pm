
var scripts = document.getElementsByTagName('script');
var path = scripts[scripts.length - 1].src.split('?')[0]; // remove any ?query
var mydir = path.split('/').slice(0, -1).join('/') + '/'; // remove last filename part of path
var fasen = ['voorbereiding', 'monitoring', 'terugkoppeling en dialoog', 'beleidsvorming', 'uitvoering', 'community building'];
var vinksrc = mydir + '../images/vinkje.png';
var rondsrc = mydir + '../images/rondje.png';
var dburl = 'https://localhost/hansei/oko/wp-content/plugins/oko-pm/getorstoredata.php';
//var gid = gemeente.id;
//var gid = 26;
//var gemeente = gemeente.naam;
var perc = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
//var scores = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];


// Load SVG 2
// Assuming you have an SVG container with a width and height
var stappenarr = [];

var icoondiv = document.getElementById("container2");
var icoonbr = (icoondiv.offsetWidth) / 5 - 10;
icoonbr = 165;

const stappen = d3.select("#container2")
    .append("svg")
    .attr("width", icoonbr * 5 + 20)
    .attr("height", 100);

var stappendata = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
var kleuren = ["#4450c6", "#3db28f", "#fabd15", "#82cae0", "#e3032c"];

var stico =
    stappen
        .selectAll("rect#stapblok")
        .data(stappendata)
        .enter()
        .append("rect")
        .attr("id", "stapblok")
        .style("fill", function (d, i) { var num = Math.floor(i / 2); return kleuren[num]; })
        .style("cursor", "pointer")
        .attr("fill-opacity", 0.30)
        .style("stroke-width", 0)
        .style("stroke", "#011763")
        .style("stroke-alignment", "outer")
        .attr("width", icoonbr - 2)
        .attr("height", 35)
        .attr("rx", 5)
        .attr("ry", 5)
        .attr("x", function (d, i) { var num = Math.floor(i / 2); return num * icoonbr; })
        .attr("y", function (d, i) { if (isEven(i)) { return 5; } return 45; })
        .on('click', function (d, i) { ToonTipsStappen(i); ToonItems(i); })
    //.on('mouseover', function (d, i) { LichtKnoppenOp(i); d3.select(this).style("cursor", "pointer"); })
    //.on('mouseout', function (d, i) { d3.select(this).style("cursor", "default"); })
    ;
var stico2 =
    stappen
        .selectAll("rect#stapblokoverlay")
        .data(stappendata)
        .enter()
        .append("rect")
        .attr("id", "stapblokoverlay")
        .style("fill", function (d, i) { var num = Math.floor(i / 2); return kleuren[num]; })
        .attr("fill-opacity", 1)
        .style("cursor", "pointer")
        .style("stroke-width", 0)
        .attr("width", 0)
        .attr("height", 35)
        .attr("rx", 5)
        .attr("ry", 5)
        .attr("x", function (d, i) { var num = Math.floor(i / 2); return num * icoonbr; })
        .attr("y", function (d, i) { if (isEven(i)) { return 5; } return 45; })
        .on('click', function (d, i) { d3.select(this).style("stroke-width", 4); ToonTipsStappen(i); ToonItems(i); })
    //.on('mouseover', function (d, i) { LichtKnoppenOp(i); d3.select(this).style("cursor", "pointer"); })
    //.on('mouseout', function (d, i) { d3.select(this).style("cursor", "default"); })
    ;
var sticotxt = stappen
    .selectAll("text#stapnr")
    .data(stappendata)
    .enter()
    .append("text")
    .attr("pointer-events", "none")
    .attr("font-weight", "normal")
    .attr("id", "stapnr")
    .attr("fill", "black")
    .style("cursor", "pointer")
    .attr("font-size", 20)
    .attr("stroke-width", 1)
    .attr("stroke", "yellow")
    .attr("stroke-opacity", 0)
    .attr("x", function (d, i) { var num = Math.floor(i / 2); return num * icoonbr + icoonbr / 2; })
    .attr("y", function (d, i) { if (isEven(i)) { return 29; } return 69; })
    .attr("fill-opacity", 1)
    .attr("text-anchor", "middle")
    .text(function (d, i) { return i + 1; })
    .attr("fill", "black")
    ;

var vinken =
    stappen
        .selectAll("text#vinken")
        .data(perc)
        .enter()
        .append("text")
        .attr("id", "vinken")
        //.attr("xlink:href", vinksrc)
        .html("&#10004;")
        .style("cursor", "pointer")
        .attr("fill", "white")
        .attr("font-size", "20")
        .attr("text-anchor", "middle")
        .attr("width", 20)
        .attr("height", 25)
        .attr("fill-opacity", 0)
        .attr("x", function (d, i) { var num = Math.floor(i / 2); return num * icoonbr + 10; })
        .attr("y", function (d, i) { if (isEven(i)) { return 25; } return 65; });


function ToonTipsFasen(z) {

    d3.select("#faseinfo").selectAll("p").remove();
    d3.select("#faseinfo").selectAll("h3").remove();
    d3.select("#faseinfo").append("p").html(faseteksten[z]);

    stappen
        .selectAll("text")
        .transition().duration(300)
        .attr("font-size", function (i) {
            var k = i - 1;
            var num = Math.floor(k / 2);
            if (num !== z) {
                return "20";
            }
            else {
                return "25";
            }
        })
        .style("stroke-opacity", function (i) {
            var k = i - 1;
            var num = Math.floor(k / 2);
            if (num !== z) {
                return 0;
            }
            else {
                return 1;
            }
        });
    stappen
        .selectAll("text")
        .transition().delay(300).duration(300)
        .attr("font-size", function (i) {
            return "20";
        })
        .style("stroke-opacity", function (i) {
            return 0;
        });
}

function isEven(number) {
    return number % 2 === 0;
}

function teken() {

    for (ss = 1; ss < 11; ss++) {
        perc[ss - 1] = scores[ss] / staptotalen[ss];
    }

    stico2
        .transition().duration(500)
        .attr("width", function (d, i) {
            var br = (icoonbr - 2) * perc[i];
            if (perc[i] < 1) {
                br = br - 2;
            }
            if (br < 0) { br = 0; }
            if (br > (icoonbr - 2)) { br = icoonbr - 2; }
            return br;
        })
        /*.attr("fill-opacity", function (d, i) {
            var x = 0.3;
            x = x + 0.7 * perc[i];
            return x;
        })*/
        ;
    vinken
        .transition().delay(500).duration(300)
        .attr("fill-opacity", function (d, i) {
            if (perc[i] > 0.999999) { return 1; };
            return 0;
        });
    sticotxt
        .transition().delay(500).duration(300)
        .attr("fill", function (d, i) {
            if (perc[i] > 0.52) { return "white"; };
            return "black";
        });
}

teken();