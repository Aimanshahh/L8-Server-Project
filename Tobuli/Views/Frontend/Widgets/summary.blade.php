<div class="widget widget-device" id="widget-summary">
    <div class="widget-heading">
        <div class="widget-title">
           
            <div class="widget-actions">
            <div class="input-group shift">
                  
                    <span class="input-group-btn">
                    <div class="btn-group dropdown">
                        <button class="btn btn-default" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Shift
                        </button>
                        <ul class="dropdown-menu">
                            <li><a id="clickMe" href="javascript:" type="button" value="clickme" onclick="fetchDataFromApi1();" role="button">Shift A</a></li>
                            <li><a id="clickMe2" href="javascript:" value="clickme" onclick="fetchDataFromApi2();" >shift B</a></li>
                            <li><a id="clickMe3" href="javascript:" value="clickme" onclick="fetchDataFromApi3();" >shift C</a></li>
                            
                        </ul>
                    </div>
                   <!-- <div class="btn-group bootstrap-select form-control">
                        <button class="btn" id="clickMe" type="button" value="clickme" onclick="fetchDataFromApi();" role="button" title="< Shift 1 >"> Shift 1</button>
                    </div> -->
                    </span>
            </div>


            </div>
            <i class="icon address"></i> {{ trans('front.summary') }} 
        </div>
    </div>
    
    <div class="widget-body table-working">
                    <?php 
           
            $device =  $device->id ?? '';
        
                ?>
                       
                       
        <table class="table">
            <tbody>
            <tr>
                <td>{{ trans('front.engine_hours') }}:</td>
                <td id="engine_hours"></td>
            </tr>
            <tr>
                <td>{{ trans('front.idle') }}:</td>
                <td id="engine_idle"></td>
            </tr>
            <tr>
                <td>{{ trans('front.total_KM') }}:</td>
                <td id="distance"></td>
            </tr>
            <tr>
                <td>{{ trans('front.trips') }}:</td>
                <td id="trips"></td>
            </tr>
           
            
            <tr>
                <td>{{ trans('front.fuel_consumption') }}:</td>
                <td></td>
            </tr>
            <tr>
                <td>{{ trans('front.move_duration') }}:</td>
                <td id="move_duration"></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
<script>
    document.getElementById("clickMe").onclick = fetchDataFromApi1;
    document.getElementById("clickMe2").onclick = fetchDataFromApi2;
    document.getElementById("clickMe3").onclick = fetchDataFromApi3;
   
    function fetchDataFromApi() {
        const userApiHash = '<?= json_encode($user->api_hash) ?>'; // Replace with your actual user API hash
        const deviceId = <?= json_encode($device) ?>;   // Define the user API hash and device ID
    if (userApiHash != (null || "")) 
    { 
    
    
    
    // Calculate start and end dates
    const startDate = new Date();
    const endDate = new Date();
    
    endDate.setDate(startDate.getDate() + 1); // Set end date to tomorrow

    const time_from = app.settings.shiftFrom_a;
    const time_to = app.settings.shiftTo_a;

    // Construct the API URL
    var apiUrl = `https://app.mapio.in/api/generate_report?user_api_hash=${userApiHash}&lang=en&date_from=${startDate.toISOString().substring(0,10)}&from_time=${time_from}&devices[]=${deviceId}&date_to=${endDate.toISOString().substring(0,10)}&to_time=${time_to}&format=jsonn&type=1&send_to_email=&generate=1`;
    
    fetch(apiUrl)
    .then(function (response) {
            return response.json();
        })
        .then(function (data) {
           console.log(data.data[0].totals); 
           appendData(data);
        })
        .catch(function (err) {
            console.log('error: ' + err);
        });

        function appendData(data) {

            var mainContainer = document.getElementById("engine_hours");
            var engineIdleContainer = document.getElementById("engine_idle");
            var distanceContainer = document.getElementById("distance");
            var moveContainer = document.getElementById("move_duration");
            var tripContainer = document.getElementById("trips");
        
            mainContainer.textContent = data.data[0].totals.engine_hours.value;

                engineIdleContainer.textContent = data.data[0].totals.engine_idle.value;


                distanceContainer.textContent = data.data[0].totals.distance.value;
                

                moveContainer.textContent = data.data[0].totals.drive_duration.value;
                

                tripContainer.textContent = data.data[0].totals.stop_count.value;

        }
    }
   
}
function fetchDataFromApi1() {
       
  // Define the user API hash and device ID
  const userApiHash = <?= json_encode($user->api_hash) ?>; // Replace with your actual user API hash
  const deviceId = <?= json_encode($device) ?>;
  
  if (userApiHash != (null || "")) 
    { 
    
    // Calculate start and end dates
    const startDate = new Date();
    const endDate = new Date();
  
   
    const time_from = app.settings.shiftFrom_a;
    const time_to = app.settings.shiftTo_a;

    // Construct the API URL
    var apiUrl = `https://app.mapio.in/api/generate_report?user_api_hash=${userApiHash}&lang=en&date_from=${startDate.toISOString().substring(0,10)}&from_time=${time_from}&devices[]=${deviceId}&date_to=${endDate.toISOString().substring(0,10)}&to_time=${time_to}&format=jsonn&type=1&send_to_email=&generate=1`;
  
    fetch(apiUrl)
        .then(function (response) {
                return response.json();
            })
            .then(function (data) {
            
                console.log(data.data[0].totals); 
                removeData(); 
            appendData(data);
            })
            .catch(function (err) {
                console.log('error: ' + err);
            });

        function appendData(data) {

            var mainContainer = document.getElementById("engine_hours");
            var engineIdleContainer = document.getElementById("engine_idle");
            var distanceContainer = document.getElementById("distance");
            var moveContainer = document.getElementById("move_duration");
            var tripContainer = document.getElementById("trips");
    
            mainContainer.textContent = data.data[0].totals.engine_hours.value;

            engineIdleContainer.textContent = data.data[0].totals.engine_idle.value;
            
            
            distanceContainer.textContent = data.data[0].totals.distance.value;
                

            moveContainer.textContent = data.data[0].totals.drive_duration.value;
                

            tripContainer.textContent = data.data[0].totals.stop_count.value;
            

        }
        function removeData() {
        
            const list1 = document.getElementById("engine_hours");
            list1.textContent = '';
            const list2 = document.getElementById("engine_idle");
            list2.textContent = '';
            const list3 = document.getElementById("distance");
            list3.textContent = '';
            const list4 = document.getElementById("move_duration");
            list4.textContent = '';
            const list5 = document.getElementById("trips");
            list5.textContent = '';
        }
    }
}
function fetchDataFromApi2() {
    
        // Define the user API hash and device ID
        const userApiHash = <?= json_encode($user->api_hash) ?>; // Replace with your actual user API hash
        const deviceId = <?= json_encode($device) ?>;
        if (userApiHash != (null || "")) 
    { 
        // Calculate start and end dates
        const startDate = new Date();
        const endDate = new Date();
        const time_from = app.settings.shiftFrom_b;
        const time_to = app.settings.shiftTo_b;
       // endDate.setDate(startDate.getDate() + 1); // Set end date to tomorrow
      // console.log(time_from);
        // Construct the API URL
        var apiUrl = `https://app.mapio.in/api/generate_report?user_api_hash=${userApiHash}&lang=en&date_from=${startDate.toISOString().substring(0,10)}&from_time=${time_from}&devices[]=${deviceId}&date_to=${endDate.toISOString().substring(0,10)}&to_time=${time_to}&format=jsonn&type=1&send_to_email=&generate=1`;
  
        fetch(apiUrl)
          .then(function (response) {
                  return response.json();
              })
              .then(function (data) {
                 console.log(data.data[0].totals);
                 removeData(); 
                 appendData(data);
              })
              .catch(function (err) {
                  console.log('error: ' + err);
              });
      
          function appendData(data) {
      
              var mainContainer = document.getElementById("engine_hours");
              var engineIdleContainer = document.getElementById("engine_idle");
              var distanceContainer = document.getElementById("distance");
              var moveContainer = document.getElementById("move_duration");
              var tripContainer = document.getElementById("trips");
             
      
                 
                  mainContainer.textContent = data.data[0].totals.engine_hours.value;

                    engineIdleContainer.textContent = data.data[0].totals.engine_idle.value;


                    distanceContainer.textContent = data.data[0].totals.distance.value;
                    

                    moveContainer.textContent = data.data[0].totals.drive_duration.value;
                    

                    tripContainer.textContent = data.data[0].totals.stop_count.value;
      
          }
                function removeData() {
                //  let text = document.getElementById("engine_hours").lastChild;
                //text.innerHTML = '';
                const list1 = document.getElementById("engine_hours");
                list1.textContent = '';
                const list2 = document.getElementById("engine_idle");
                list2.textContent = '';
                const list3 = document.getElementById("distance");
                list3.textContent = '';
                const list4 = document.getElementById("move_duration");
                list4.textContent = '';
                const list5 = document.getElementById("trips");
                list5.textContent = '';
            }
        }
      }
function fetchDataFromApi3() {
      
        // Define the user API hash and device ID
        const userApiHash = <?= json_encode($user->api_hash) ?>; // Replace with your actual user API hash
        const deviceId = <?= json_encode($device) ?>;
        
        if (userApiHash != (null || "")) 
        { 
        // Calculate start and end dates
        const startDate = new Date();
        const endDate = new Date();
        const time_from = app.settings.shiftFrom_c;
        const time_to = app.settings.shiftTo_c;
        endDate.setDate(endDate.getDate() + 1); // Set end date to tomorrow
      
        // Construct the API URL
        var apiUrl = `https://app.mapio.in/api/generate_report?user_api_hash=${userApiHash}&lang=en&date_from=${startDate.toISOString().substring(0,10)}&from_time=${time_from}&devices[]=${deviceId}&date_to=${endDate.toISOString().substring(0,10)}&to_time=${time_to}&format=jsonn&type=1&send_to_email=&generate=1`;
  
        fetch(apiUrl)
          .then(function (response) {
                  return response.json();
              })
              .then(function (data) {
                 console.log(data.data[0].totals); 
                 removeData();
                 appendData(data);
              })
              .catch(function (err) {
                  console.log('error: ' + err);
              });
      
            function appendData(data) {
        
                var mainContainer = document.getElementById("engine_hours");
                var engineIdleContainer = document.getElementById("engine_idle");
                var distanceContainer = document.getElementById("distance");
                var moveContainer = document.getElementById("move_duration");
                var tripContainer = document.getElementById("trips");
                
                
                    mainContainer.textContent = data.data[0].totals.engine_hours.value;

                    engineIdleContainer.textContent = data.data[0].totals.engine_idle.value;


                    distanceContainer.textContent = data.data[0].totals.distance.value;
                    

                    moveContainer.textContent = data.data[0].totals.drive_duration.value;
                    

                    tripContainer.textContent = data.data[0].totals.stop_count.value;
                        
            }
          function removeData() {
        
            const list1 = document.getElementById("engine_hours");
            list1.textContent = '';
            const list2 = document.getElementById("engine_idle");
            list2.textContent = '';
            const list3 = document.getElementById("distance");
            list3.textContent = '';
            const list4 = document.getElementById("move_duration");
            list4.textContent = '';
            const list5 = document.getElementById("trips");
            list5.textContent = '';
        }
    }
      }
window.onload = fetchDataFromApi1();

</script>