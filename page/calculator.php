<?php session_start();?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculator</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
            margin: 0;
            font-family: 'Arial', sans-serif;
            padding-top: 60px; /* Adjust this value based on your header height */
        }

        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background-color: #fff;
            border-bottom: 1px solid #ddd;
            z-index: 1000;
        }

        .calculator {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 20px; /* Adjust spacing if necessary */
        }

        .calculator-display {
            background-color: #222;
            color: #fff;
            padding: 20px;
            user-select: none;
        }

        .calculator-operation {
            font-size: 1.2rem;
            opacity: 0.7;
        }

        .calculator-result {
            font-size: 2.5rem;
            text-align: right;
        }

        .calculator-keys {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
        }

        .key {
            background-color: #f2f2f2;
            border: none;
            font-size: 1.5rem;
            padding: 20px;
            cursor: pointer;
        }

        .key:hover {
            background-color: #e2e2e2;
        }

        .key[data-action="add"],
        .key[data-action="subtract"],
        .key[data-action="multiply"],
        .key[data-action="divide"] {
            background-color: #d0d0d0;
        }

        .key[data-action="equals"] {
            background-color: #ff9500;
            color: white;
        }

        .key[data-action="clear"],
        .key[data-action="delete"] {
            background-color: #ff3b30;
            color: white;
        }
    </style>
</head>
<body>
    <header class="header">
        <?php include('../templates/header.php'); ?>
    </header>
    <main>
        <div class="calculator">
            <div class="calculator-display">
                <div class="calculator-operation" id="operation"></div>
                <div class="calculator-result" id="result">0</div>
            </div>
            <div class="calculator-keys">
                <button class="key" data-action="clear">AC</button>
                <button class="key" data-action="delete">DEL</button>
                <button class="key" data-action="divide">÷</button>
                <button class="key" data-action="multiply">×</button>
                <button class="key">7</button>
                <button class="key">8</button>
                <button class="key">9</button>
                <button class="key" data-action="subtract">−</button>
                <button class="key">4</button>
                <button class="key">5</button>
                <button class="key">6</button>
                <button class="key" data-action="add">+</button>
                <button class="key">1</button>
                <button class="key">2</button>
                <button class="key">3</button>
                <button class="key" data-action="equals">=</button>
                <button class="key">0</button>
                <button class="key" data-action="decimal">.</button>
            </div>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resultDisplay = document.getElementById('result');
            const operationDisplay = document.getElementById('operation');
            const keys = document.querySelector('.calculator-keys');
            let displayValue = '0';
            let firstValue = null;
            let operator = null;
            let awaitingSecondValue = false;

            function updateDisplay() {
                resultDisplay.textContent = displayValue;
                if (operator && awaitingSecondValue) {
                    operationDisplay.textContent = `${firstValue} ${operator}`;
                } else {
                    operationDisplay.textContent = '';
                }
            }

            function clear() {
                displayValue = '0';
                firstValue = null;
                operator = null;
                awaitingSecondValue = false;
                updateDisplay();
            }

            function deleteLast() {
                if (displayValue.length > 1) {
                    displayValue = displayValue.slice(0, -1);
                } else {
                    displayValue = '0';
                }
                updateDisplay();
            }

            function inputDigit(digit) {
                if (awaitingSecondValue) {
                    displayValue = digit;
                    awaitingSecondValue = false;
                } else {
                    displayValue = displayValue === '0' ? digit : displayValue + digit;
                }
                updateDisplay();
            }

            function inputDecimal() {
                if (!displayValue.includes('.')) {
                    displayValue += '.';
                    updateDisplay();
                }
            }

            function handleOperator(nextOperator) {
                const inputValue = parseFloat(displayValue);

                if (operator && awaitingSecondValue) {
                    operator = nextOperator;
                    return;
                }

                if (firstValue === null && !isNaN(inputValue)) {
                    firstValue = inputValue;
                } else if (operator) {
                    const result = performCalculation[operator](firstValue, inputValue);
                    displayValue = `${parseFloat(result.toFixed(7))}`;
                    firstValue = result;
                }

                awaitingSecondValue = true;
                operator = nextOperator;
                updateDisplay();
            }

            const performCalculation = {
                '/': (firstValue, secondValue) => firstValue / secondValue,
                '*': (firstValue, secondValue) => firstValue * secondValue,
                '+': (firstValue, secondValue) => firstValue + secondValue,
                '-': (firstValue, secondValue) => firstValue - secondValue,
                '=': (firstValue, secondValue) => secondValue
            };

            keys.addEventListener('click', event => {
                const { target } = event;
                const { action } = target.dataset;
                const keyContent = target.textContent;
                const displayedNum = displayValue;

                if (!target.matches('button')) {
                    return;
                }

                if (target.classList.contains('key')) {
                    switch (action) {
                        case 'clear':
                            clear();
                            break;
                        case 'delete':
                            deleteLast();
                            break;
                        case 'decimal':
                            inputDecimal();
                            break;
                        case 'add':
                        case 'subtract':
                        case 'multiply':
                        case 'divide':
                        case 'equals':
                            handleOperator(action === 'add' ? '+' :
                                        action === 'subtract' ? '-' :
                                        action === 'multiply' ? '*' :
                                        action === 'divide' ? '/' : '=');
                            break;
                        default:
                            inputDigit(keyContent);
                            break;
                    }
                }
            });

            updateDisplay();
        });
    </script>
</body>
</html>
