<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Data or Airtime</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .toggle-buttons {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .toggle-buttons button {
            width: 45%;
            padding: 12px;
            border: none;
            cursor: pointer;
            border-radius: 8px;
            background-color: #fff;
            color: #333;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, box-shadow 0.3s;
            font-size: 16px;
        }
        .toggle-buttons button.active {
            background-color: #007bff;
            color: white;
        }
        .button-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .button-container div {
            background-color: #fff;
            color: #333;
            height: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .button-container div:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        .button-container div i {
            font-size: 26px;
            margin-bottom: 8px;
        }
        form button[type="submit"] {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            border: none;
            border-radius: 8px;
            background-color: #007bff;
            color: white;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        form button[type="submit"]:hover {
            background-color: #0056b3;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Buy Data or Airtime</h1>
        <!-- Toggle Buttons -->
        <div class="toggle-buttons">
            <button id="dataBtn" class="active">Data</button>
            <button id="airtimeBtn">Airtime</button>
        </div>

        <form method="POST" action="purchase.php">
            <input type="hidden" id="type" name="type">
            <input type="hidden" id="provider" name="provider" value="MTN">
            <input type="hidden" id="amount" name="amount">

            <!-- Data Packages -->
            <div id="dataPackages" class="button-container">
                <?php
                $dataPackages = [
                    ['150 MB', 5, 'fas fa-wifi'],
                    ['1 GB', 10, 'fas fa-wifi'],
                    ['2 GB', 15, 'fas fa-wifi'],
                    ['5 GB', 25, 'fas fa-wifi'],
                    ['10 GB', 40, 'fas fa-wifi'],
                    ['25 GB', 70, 'fas fa-wifi']
                ];
                foreach ($dataPackages as $package) {
                    echo "<div data-type='data' data-amount='{$package[0]}'>
                            <i class='{$package[2]}'></i>
                            <span>{$package[0]} - \${$package[1]}</span>
                          </div>";
                }
                ?>
            </div>

            <!-- Airtime Packages -->
            <div id="airtimePackages" class="button-container hidden">
                <?php
                $airtimePackages = [
                    ['$5', 'fas fa-phone-alt'],
                    ['$10', 'fas fa-phone-alt'],
                    ['$20', 'fas fa-phone-alt']
                ];
                foreach ($airtimePackages as $package) {
                    echo "<div data-type='airtime' data-amount='{$package[0]}'>
                            <i class='{$package[1]}'></i>
                            <span>{$package[0]}</span>
                          </div>";
                }
                ?>
            </div>

            <button type="submit">Buy</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>

    <script>
        // Toggle buttons between Data and Airtime
        document.getElementById('dataBtn').addEventListener('click', function() {
            document.getElementById('dataPackages').classList.remove('hidden');
            document.getElementById('airtimePackages').classList.add('hidden');
            document.getElementById('dataBtn').classList.add('active');
            document.getElementById('airtimeBtn').classList.remove('active');
        });

        document.getElementById('airtimeBtn').addEventListener('click', function() {
            document.getElementById('airtimePackages').classList.remove('hidden');
            document.getElementById('dataPackages').classList.add('hidden');
            document.getElementById('airtimeBtn').classList.add('active');
            document.getElementById('dataBtn').classList.remove('active');
        });

        document.querySelectorAll('.button-container div').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('type').value = this.getAttribute('data-type');
                document.getElementById('amount').value = this.getAttribute('data-amount');
                document.querySelectorAll('.button-container div').forEach(btn => {
                    btn.style.backgroundColor = '#fff';
                });
                this.style.backgroundColor = '#f0f0f0';
            });
        });
    </script>
</body>
</html>
