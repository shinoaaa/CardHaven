const checkBox = document.getElementById("checkbox");
    let clicked = true;

    checkBox.addEventListener('click', () => {

    if(clicked){
        checkBox.style.backgroundColor = '#0088FF';
        checkBox.style.color = 'white'
        clicked = false;
    }
    else{
        checkBox.style.backgroundColor = '';
        checkBox.style.color = '#0088FF'
        clicked = true;
    }
})


