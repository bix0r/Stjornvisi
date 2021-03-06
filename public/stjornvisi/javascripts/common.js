/**
 * Created by einar on 18/01/15.
 */

;(function(){
    "use strict";


    var mock = {addEventListener:function(){}};
    var active = undefined;
    (document.querySelector('.categories__groups')||mock).addEventListener('click',function(event){
        event.preventDefault();
        document.querySelector('ul.navigation').style.marginLeft = 0;
        if(active == undefined){
            event.target.classList.add('active');
            document.body.classList.add('state-open');
            active = event.target;
        }
        else if( active == event.target ){
            event.target.classList.remove('active');
            document.body.classList.remove('state-open');
            active = undefined;
        }else{
            active.classList.remove('active');
            active = event.target;
            active.classList.add('active');
        }


    },false);
    (document.querySelector('.categories__users')||mock).addEventListener('click',function(event){
        event.preventDefault();
        document.querySelector('ul.navigation').style.marginLeft = '-100%';
        if(active == undefined){
            event.target.classList.add('active');
            document.body.classList.add('state-open');
            active = event.target;
        }
        else if( active == event.target ){
            event.target.classList.remove('active');
            document.body.classList.remove('state-open');
            active = undefined;
        }else{
            active.classList.remove('active');
            active = event.target;
            active.classList.add('active');
        }
    },false);
    (document.querySelector('.categories__config')||mock).addEventListener('click',function(event){
        event.preventDefault();
        document.querySelector('ul.navigation').style.marginLeft = '-200%';
        if(active == undefined){
            event.target.classList.add('active');
            document.body.classList.add('state-open');
            active = event.target;
        }
        else if( active == event.target ){
            event.target.classList.remove('active');
            document.body.classList.remove('state-open');
            active = undefined;
        }else{
            active.classList.remove('active');
            active = event.target;
            active.classList.add('active');
        }
    },false);

    (document.querySelector('.main__burger')||mock).addEventListener('click',function(event){
        event.preventDefault();
        document.body.classList.toggle('state-mobile-menu-open');
    },false);

    (document.querySelector('.main__home')||mock).addEventListener('click',function(event){
        event.preventDefault();
        document.body.classList.toggle('state-open');
    },false);


})();