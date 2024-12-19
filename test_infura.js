import Web3 from 'web3';

// Your Infura Project ID
const INFURA_PROJECT_ID = '4c7b35f5778344edbcf5063c06a9fdd7';

// Network configurations
const networks = {
    ethereum: `https://mainnet.infura.io/v3/${INFURA_PROJECT_ID}`,
    polygon: `https://polygon-mainnet.infura.io/v3/${INFURA_PROJECT_ID}`,
    bsc: `https://bsc-mainnet.infura.io/v3/${INFURA_PROJECT_ID}`
};

// USDT Contract addresses
const usdtAddresses = {
    ethereum: '0xdAC17F958D2ee523a2206206994597C13D831ec7',
    bsc: '0x55d398326f99059fF775485246999027B3197955',
    polygon: '0xc2132D05D31c914a87C6611C10748AEb04B58e8F'
};

// Minimal ERC20 ABI for name() function
const minimalABI = [
    {
        "constant": true,
        "inputs": [],
        "name": "name",
        "outputs": [{"name": "", "type": "string"}],
        "payable": false,
        "stateMutability": "view",
        "type": "function"
    }
];

async function testNetwork(networkName, rpcUrl) {
    try {
        const web3 = new Web3(rpcUrl);
        
        console.log(`\nTesting ${networkName.toUpperCase()} Network:`);
        
        // Test basic connection
        const blockNumber = await web3.eth.getBlockNumber();
        console.log(`✅ Connected successfully! Current block: ${blockNumber}`);
        
        // Test USDT contract
        if (usdtAddresses[networkName]) {
            const contract = new web3.eth.Contract(minimalABI, usdtAddresses[networkName]);
            try {
                const name = await contract.methods.name().call();
                console.log(`✅ USDT Contract accessible! Token name: ${name}`);
            } catch (error) {
                console.log(`❌ USDT Contract error: ${error.message}`);
            }
        }
        
    } catch (error) {
        console.log(`❌ Connection failed: ${error.message}`);
    }
}

async function runTests() {
    console.log('🔍 Testing Infura Connections...\n');
    
    for (const [network, url] of Object.entries(networks)) {
        await testNetwork(network, url);
    }
}

// Run the tests
runTests().catch(console.error);
