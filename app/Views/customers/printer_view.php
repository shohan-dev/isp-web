<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bluetooth Printer Web App</title>
</head>
<body>
    <h1>Bluetooth POS Printer</h1>

    <label for="message">Message to Print:</label>
    <input type="text" id="message" placeholder="Enter text here">
    
    <div id="receipt" style="border: 1px solid #000; padding: 10px; width: 200px;">
        <h2>Sample Receipt</h2>
        <p>Item 1 - $10.00</p>
        <p>Item 2 - $15.00</p>
        <p>Total - $25.00</p>
    </div>
    
    <button id="connect">Connect Printer</button>
    <button id="print" disabled>Print</button>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js" 
            integrity="sha512-01CJ9/g7e8cUmY0DFTMcUw/ikS799FHiOA0eyHsUWfOetgbx/t6oV4otQ5zXKQyIrQGTHSmRVPIgrgLcZi/WMA==" 
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        let printCharacteristic;
        let printButton = document.getElementById("print");
        let connectButton = document.getElementById("connect");
        let messageInput = document.getElementById("message");
        let receipt = document.getElementById("receipt");

        // Function to connect to the Bluetooth printer
        async function connectPrinter() {
            try {
                console.log('Requesting Bluetooth device...');
                const device = await navigator.bluetooth.requestDevice({
                    filters: [{ services: ['000018f0-0000-1000-8000-00805f9b34fb'] }]
                });
                console.log('> Found ' + device.name);
                const server = await device.gatt.connect();
                const service = await server.getPrimaryService("000018f0-0000-1000-8000-00805f9b34fb");
                printCharacteristic = await service.getCharacteristic("00002af1-0000-1000-8000-00805f9b34fb");
                console.log('> Connected to printer');
                printButton.disabled = false; // Enable the print button after connection
            } catch (error) {
                console.error('Error connecting to printer:', error);
            }
        }

        connectButton.addEventListener("click", connectPrinter);

        // Function to print the receipt data
        async function printReceipt() {
            try {
                const imageData = await domtoimage.toPng(receipt);
                const text = messageInput.value + '\n'; // Add message to be printed
                
                // Convert the image data to binary
                const imageDataBinary = await fetch(imageData)
                    .then(response => response.arrayBuffer());

                // Send the image data and message to the Bluetooth printer
                if (printCharacteristic) {
                    // Send image data
                    await printCharacteristic.writeValue(new Uint8Array(imageDataBinary));
                    // Send text data (message input)
                    const encoder = new TextEncoder();
                    const textBytes = encoder.encode(text);
                    await printCharacteristic.writeValue(textBytes);
                    
                    console.log('Print successful');
                } else {
                    console.log('Printer not connected');
                }
            } catch (error) {
                console.error('Error printing:', error);
            }
        }

        printButton.addEventListener("click", printReceipt);

        // Optionally, auto-connect the printer when the page loads
        window.addEventListener('load', connectPrinter);
    </script>
</body>
</html>
