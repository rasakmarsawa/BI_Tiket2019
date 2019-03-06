<?php
include 'koneksi.php';
include 'data.php';

$data = new data();
//$data->export_csv($data->get_pemesanan_monthly());
 ?>

 <style>
 body {
   font-family: Helvetica Neue, Helvetica-Neue, Helvetica;
 }

 .chart {
   float: left;
 }

 .button-area {
   padding: 40px 0 0 30px;
   float: left;
 }

 .app-button {
   display: block;
   margin-bottom: 3px;
 }

 .axis text {
   font-size: 0.8em;
 }

 .axis line {
   stroke: #000;
 }

 .axis path {
   display: none;
 }

 .line {
   stroke: orange;
   fill: none;
   stroke-width: 3px;
 }

 .overlay {
   fill: none;
   pointer-events: all;
 }

 .tooltip {
   position: absolute;
   padding: 10px;
   font: 12px sans-serif;
   background: #222;
   color: #fff;
   border: 0px;
   border-radius: 8px;
   pointer-events: none;
   opacity: 0.9;
   visibility: hidden;
 }
 </style>

 <script src="//d3js.org/d3.v3.min.js"></script>
 <script>
 d3.csv('data.csv', function(day_data) {
   var formatDate = d3.time.format("%Y-%m-%d");
   var bisectDate = d3.bisector(function(d) { return formatDate.parse(d['date']); }).left;

   // fix those integers
   day_data.forEach(function(d, i) {
     d.value = parseInt(d.value);
   });

   ///////////////////////
   // generate weekly data
   var week_data = create_time_unit_data(parse_for_week);

   function parse_for_week(date) {
     return formatDate.parse(date).getDay();
   }

   ///////////////////////
   // generate monthly data
   var month_data = create_time_unit_data(parse_for_month);

   function parse_for_month(date) {
     return formatDate.parse(date).getDate() - 1;
   }

   ///////////////////////
   // helper functions
   function create_time_unit_data(parse_date) {
     var new_data = JSON.parse(JSON.stringify(day_data));
     new_data.forEach(function(d, i) {
       var offset = parse_date(d.date);
       if(offset == 0 || i == 0 || i == day_data.length - 1) { // it's a new date_unit, or this is the first or last date in the array
         d['start'] = true;
       } else {
         d['start'] = false;

         // add this value to the start of the week
         var tar_idx = i - offset;
         if(tar_idx < 0) { tar_idx = 0; }
         new_data[tar_idx].value += d.value;

         // then nil out
         d.value = -1;
       }
     });

     fill_in_gaps(new_data);
     return new_data;
   }

   function fill_in_gaps(data_array) {
     // go back in and fill in the missing dates
     data_array.forEach(function(d, i) {
       if(d.start == false) {
         var prev_val, next_val, prev_dist, next_dist;
         for(var idx = i; idx>=0; idx--) {
           if(data_array[idx].start == true) {
             prev_val = data_array[idx].value;
             prev_dist = i - idx;
             break;
           }
         }

         for(var idx = i; idx < data_array.length; idx++) {
           if(data_array[idx].start == true) {
             next_val = data_array[idx].value;
             next_dist = idx - i;
             break;
           }
         }

         d.value = prev_val + ((next_val - prev_val) * (prev_dist/ (prev_dist + next_dist)));
       }
     });
   }

   ///////////////////////
   // Time unit selection buttons
   var all_data = [

   ];

   d3.select('.button-area').selectAll('.app-button')
     .data(all_data)
     .enter().append('button')
     .attr('class', 'app-button')
     .html(function(d) { return d.name; })
     .on('click', function(d) {
       curr_data = d.data;
       updateChart();
     });

   ///////////////////////
   // Chart Size Setup
   var margin = { top: 40, right: 50, bottom: 20, left: 20 };

   var width = 840 - margin.left - margin.right;
   var height = 500 - margin.top - margin.bottom;

   var chart = d3.select(".chart")
       .attr("width", 840)
       .attr("height", 500)
     .append("g")
       .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

   ///////////////////////
   // Title
   chart.append("text")
     .text("Grafik Garis Penjualan Tiket Februari 2017 - Februari 2018")
     .attr("text-anchor", "middle")
     .attr("class", "graph-title")
     .attr("y", -10)
     .attr("x", width / 2.0);

   chart.append("text")
     .attr("text-anchor", "middle")
     .attr("style", "font-size: 0.9em")
     .attr("y", height + 50)
     .attr("x", width / 2.0);

   ///////////////////////
   // Scales
   var x = d3.time.scale()
       .domain(d3.extent(day_data, function(d) { return formatDate.parse(d.date); }))
       .range([0, width]);

   var y;

   ///////////////////////
   // Axis
   var xAxis = d3.svg.axis()
       .scale(x)
       .orient("bottom");

   chart.append("g")
       .attr("class", "x axis")
       .attr("transform", "translate(0," + height + ")")
       .call(xAxis);

   ///////////////////////
   // Tooltips
   var overlay = chart.append("rect")
       .attr("class", "overlay")
       .attr("width", width)
       .attr("height", height)
       .on("mouseover", function() { chart.selectAll('.focus').style("display", null); })
       .on("mouseout", function() { chart.selectAll('.focus').style("display", "none"); })

   var tooltip = d3.select("body").append("div")
       .attr("class", "tooltip");

   var focus = chart.append("g")
       .attr("class", "focus")
       .style("display", "none");

   focus.append("circle")
       .attr("r", 4.5)

   focus.append("text")
       .attr("x", 9)
       .attr("dy", ".35em");

   ///////////////////////
   // DYNAMIC STUFF GOES HERE
   // any data things that need to update (yxis, lines, etc)
   function updateChart() {
     ///////////////////////
     // Y axis changes
     y = d3.scale.linear()
         .domain([0, d3.max(curr_data, function(d) { return d.value; })])
         .range([height, 0]);

     var yAxis = d3.svg.axis()
         .scale(y)
         .orient("right");

     // remove any old axis
     chart.selectAll(".y-axis").remove()

     chart.append("g")
         .attr("class", "y axis y-axis")
         .attr("transform", "translate(" + (width + 5) + ",0)")
         .call(yAxis);

     ///////////////////////
     // Line changes
     var lineGenerator = d3.svg.line()
         .x(function(d) { return x(formatDate.parse(d.date)) })
         .y(function(d) { return y(d.value) });

     var lines = chart.selectAll(".line")
         .data([curr_data])

     lines.enter().append("path")
         .attr("class", "line")
         .attr("d", lineGenerator);

     lines.transition()
         .duration(1000)
         .attr("d", lineGenerator);

     overlay.on("mousemove", mousemove);
   }

   function mousemove() {
     var x0 = x.invert(d3.mouse(this)[0]),
         i = bisectDate(curr_data, x0, 1),
         d0 = curr_data[i - 1],
         d1 = curr_data[i];

     // search for dates where "start" == true, in other words, only valid dates for the dataset
     // start of week, start of month
     var i0 = i - 1;
     while(d0.start == false && i0 > 0) {
       i0 -= 1;
       d0 = curr_data[i0];
     }
     var i1 = i;
     while(d1.start == false && i1 < curr_data.length - 1) {
       i1 += 1;
       d1 = curr_data[i1];
     }

     var dIdx = x0 - d0.date > d1.date - x0 ? i1 : i0;

     var tar_date = curr_data[dIdx]['date'];
     var tooltip_string = tar_date;

     var tar_value = curr_data[dIdx].value;
     focus.attr("transform", "translate(" + x(formatDate.parse(tar_date)) + ","+y(tar_value)+ ")");
     tooltip_string += "<br>" + tar_value;

     tooltip.html(tooltip_string)
       .style("visibility", "visible")
       .style("top", d3.mouse(this)[1] - (tooltip[0][0].clientHeight - 30) + "px")
       .style("left", d3.mouse(this)[0] - (tooltip[0][0].clientWidth / 2.0) + "px");
   }

   // default
   curr_data = day_data;
   updateChart();
 });
 </script>

 <body>
   <svg class="chart"></svg>
   <div class='button-area'>
   </div>
 </body>
